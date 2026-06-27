package handler

import (
	"net/http"
	"path/filepath"
	"strconv"
	"strings"

	"backend/internal/model"
	"backend/internal/service"
	"backend/pkg/utils"

	"backend/pkg/storage"

	"github.com/gin-gonic/gin"
)

type ScanHandler struct {
	Service *service.ScanService
	Storage storage.StorageService
}

func NewScanHandler(s *service.ScanService, st storage.StorageService) *ScanHandler {
	return &ScanHandler{Service: s, Storage: st}
}

func (h *ScanHandler) UploadScan(c *gin.Context) {
	user := c.MustGet("user").(model.User)

	patientIDStr := c.PostForm("patient_id")
	patientID, err := strconv.Atoi(patientIDStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid patient ID"})
		return
	}

	file, err := c.FormFile("image")
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Image file is required"})
		return
	}

	// Validate extension
	ext := strings.ToLower(filepath.Ext(file.Filename))
	if ext != ".jpg" && ext != ".jpeg" && ext != ".png" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Only JPG and PNG are allowed"})
		return
	}

	// Validate size (5MB = 5 * 1024 * 1024)
	if file.Size > 5*1024*1024 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "File size exceeds 5MB"})
		return
	}

	// Use StorageService to upload securely
	imagePath, err := h.Storage.UploadFile(file, "ct_scans")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to upload file to cloud storage"})
		return
	}

	scan, err := h.Service.UploadScan(user.ID, patientID, imagePath)
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Upload failed", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Scan uploaded successfully", scan)
}

func (h *ScanHandler) AnalyzeScan(c *gin.Context) {
	scanID, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid scan ID"})
		return
	}

	// Return immediately — AI analysis runs in background goroutine
	utils.SuccessResponse(c, http.StatusAccepted, "AI analysis queued", gin.H{"scan_id": scanID})

	// Run AI analysis asynchronously so the response is not blocked
	go func() {
		_, _ = h.Service.AnalyzeScan(scanID)
	}()
}

func (h *ScanHandler) AssignDoctor(c *gin.Context) {
	scanID, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid scan ID"})
		return
	}

	var req struct {
		DoctorID int `json:"doctor_id" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := h.Service.AssignDoctor(scanID, req.DoctorID); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to assign doctor", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Doctor assigned successfully", nil)
}

// GetScans returns scans with optional query params:
// ?page=1&limit=10&status=pending_review&sort=latest&start_date=2026-01-01&end_date=2026-12-31
func (h *ScanHandler) GetScans(c *gin.Context) {
	user := c.MustGet("user").(model.User)

	// Parse pagination params
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "0")) // 0 = no limit (backwards compat)
	statusFilter := c.Query("status")
	sort := c.DefaultQuery("sort", "latest")
	startDate := c.Query("start_date")
	endDate := c.Query("end_date")

	if page < 1 {
		page = 1
	}

	var scans []model.CTScan
	var err error

	if user.RoleName == "Patient" {
		scans, err = h.Service.GetScansByPatientUser(user.ID)
	} else if user.RoleName == "Doctor" {
		scans, err = h.Service.GetScansByDoctor(user.ID)
	} else {
		scans, err = h.Service.GetAllScans()
	}

	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	if scans == nil {
		scans = []model.CTScan{}
	}

	// Apply server-side status filter
	if statusFilter != "" {
		filtered := scans[:0]
		for _, s := range scans {
			if s.Status == statusFilter {
				filtered = append(filtered, s)
			}
		}
		scans = filtered
	}

	// Apply date range filter
	if startDate != "" || endDate != "" {
		filtered := scans[:0]
		for _, s := range scans {
			dateStr := s.CreatedAt.Format("2006-01-02")
			if startDate != "" && dateStr < startDate {
				continue
			}
			if endDate != "" && dateStr > endDate {
				continue
			}
			filtered = append(filtered, s)
		}
		scans = filtered
	}

	// Apply sort
	if sort == "oldest" {
		for i, j := 0, len(scans)-1; i < j; i, j = i+1, j-1 {
			scans[i], scans[j] = scans[j], scans[i]
		}
	}
	// "latest" is default from DB (ORDER BY created_at DESC)

	// Apply pagination
	total := len(scans)
	totalPages := 1
	if limit > 0 {
		totalPages = (total + limit - 1) / limit
		if totalPages < 1 {
			totalPages = 1
		}
		start := (page - 1) * limit
		end := start + limit
		if start > total {
			scans = []model.CTScan{}
		} else {
			if end > total {
				end = total
			}
			scans = scans[start:end]
		}
	}

	utils.PaginatedResponse(c, http.StatusOK, "Scans fetched successfully", scans, page, limit, total)
}

func (h *ScanHandler) SubmitDiagnosis(c *gin.Context) {
	user := c.MustGet("user").(model.User)

	var diag model.Diagnosis
	if err := c.ShouldBindJSON(&diag); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	diag.DoctorID = user.ID
	if err := h.Service.SubmitDiagnosis(&diag); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to submit diagnosis", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Diagnosis draft created", diag)
}

func (h *ScanHandler) ApproveDiagnosis(c *gin.Context) {
	diagID, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid diagnosis ID"})
		return
	}

	var req struct {
		ScanID int `json:"scan_id" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := h.Service.ApproveDiagnosis(diagID, req.ScanID); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to approve diagnosis", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Diagnosis and Scan approved", nil)
}

func (h *ScanHandler) RejectDiagnosis(c *gin.Context) {
	diagID, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid diagnosis ID"})
		return
	}

	var req struct {
		ScanID int `json:"scan_id" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Update diagnosis status to rejected/cancelled, or just reject the scan status
	if err := h.Service.RejectScan(req.ScanID); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to reject scan", err.Error())
		return
	}

	// Also update diagnosis status to a final state like "rejected"
	_ = h.Service.ScanRepo.UpdateDiagnosisStatus(diagID, "rejected")

	utils.SuccessResponse(c, http.StatusOK, "Scan rejected successfully", nil)
}


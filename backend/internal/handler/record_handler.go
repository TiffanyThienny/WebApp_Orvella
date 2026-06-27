package handler

import (
	"fmt"
	"net/http"

	"backend/internal/model"
	"backend/internal/service"
	"backend/pkg/utils"

	"github.com/gin-gonic/gin"
)

type RecordHandler struct {
	Service *service.RecordService
}

func NewRecordHandler(s *service.RecordService) *RecordHandler {
	return &RecordHandler{Service: s}
}

func (h *RecordHandler) CreateRecord(c *gin.Context) {
	user := c.MustGet("user").(model.User)
	var rec model.HealthRecord
	if err := c.ShouldBindJSON(&rec); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request payload", err.Error())
		return
	}

	// Specific Vitals Validation for better UX
	if rec.Systolic <= 0 {
		utils.ErrorResponse(c, http.StatusBadRequest, "Tekanan darah (Systolic) harus angka positif", nil)
		return
	}
	if rec.Diastolic <= 0 {
		utils.ErrorResponse(c, http.StatusBadRequest, "Tekanan darah (Diastolic) harus angka positif", nil)
		return
	}
	if rec.HeartRate <= 0 {
		utils.ErrorResponse(c, http.StatusBadRequest, "Detak jantung (Heart Rate) harus angka positif", nil)
		return
	}
	if rec.Weight <= 0 {
		utils.ErrorResponse(c, http.StatusBadRequest, "Berat badan harus angka positif", nil)
		return
	}
	if rec.OxygenLevel < 0 || rec.OxygenLevel > 100 {
		utils.ErrorResponse(c, http.StatusBadRequest, "Kadar oksigen harus antara 0-100%", nil)
		return
	}

	rec.CreatedBy = user.ID

	if err := h.Service.CreateRecord(&rec); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to create record", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Record created successfully", nil)
}

func (h *RecordHandler) GetGraphData(c *gin.Context) {
	user := c.MustGet("user").(model.User)
	targetUserID := user.ID

	if user.RoleName == "Doctor" || user.RoleName == "Admin" {
		pUserIDStr := c.Query("patient_user_id")
		if pUserIDStr != "" {
			if _, err := fmt.Sscanf(pUserIDStr, "%d", &targetUserID); err != nil {
				utils.ErrorResponse(c, http.StatusBadRequest, "Invalid patient_user_id", nil)
				return
			}
		}
	} else if user.RoleName != "Patient" {
		utils.ErrorResponse(c, http.StatusForbidden, "Unauthorized access to graphs", nil)
		return
	}

	records, err := h.Service.GetGraphData(targetUserID)
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch graph data", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Graph data fetched successfully", records)
}

func (h *RecordHandler) UpdateRecord(c *gin.Context) {
	idStr := c.Param("id")
	var id int
	if _, err := fmt.Sscanf(idStr, "%d", &id); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid ID", nil)
		return
	}

	var rec model.HealthRecord
	if err := c.ShouldBindJSON(&rec); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid payload", err.Error())
		return
	}
	rec.ID = id

	if err := h.Service.UpdateRecord(&rec); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to update record", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Record updated successfully", nil)
}

func (h *RecordHandler) DeleteRecord(c *gin.Context) {
	idStr := c.Param("id")
	var id int
	if _, err := fmt.Sscanf(idStr, "%d", &id); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid ID", nil)
		return
	}

	if err := h.Service.DeleteRecord(id); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to delete record", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Record deleted successfully", nil)
}

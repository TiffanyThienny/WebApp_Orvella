package handler

import (
	"encoding/csv"
	"fmt"
	"net/http"
	"strconv"

	"backend/internal/model"
	"backend/internal/repository"
	"backend/pkg/utils"

	"github.com/gin-gonic/gin"
)

type ExportHandler struct {
	UserRepo   *repository.UserRepository
	ScanRepo   *repository.ScanRepository
	RecordRepo *repository.RecordRepository
}

func NewExportHandler(ur *repository.UserRepository, sr *repository.ScanRepository, rr *repository.RecordRepository) *ExportHandler {
	return &ExportHandler{UserRepo: ur, ScanRepo: sr, RecordRepo: rr}
}

func (h *ExportHandler) ExportScans(c *gin.Context) {
	scans, err := h.ScanRepo.GetAllScans()
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch scans", err.Error())
		return
	}

	c.Header("Content-Type", "text/csv")
	c.Header("Content-Disposition", "attachment;filename=scans_export.csv")

	writer := csv.NewWriter(c.Writer)
	defer writer.Flush()

	// Header updated with AI & Diagnosis info
	writer.Write([]string{"ID", "PatientID", "Status", "AI Prediction", "Confidence", "Risk Level", "Doctor Diagnosis", "ImageURL", "CreatedAt"})

	for _, s := range scans {
		aiLabel := "N/A"
		aiConf := "N/A"
		aiRisk := "N/A"
		diagNotes := "N/A"

		if s.AIResult != nil {
			aiLabel = s.AIResult.PredictionLabel
			aiConf = strconv.FormatFloat(s.AIResult.Confidence, 'f', 2, 64)
			aiRisk = s.AIResult.RiskLevel
		}
		if s.Diagnosis != nil {
			diagNotes = s.Diagnosis.Notes
		}

		writer.Write([]string{
			strconv.Itoa(s.ID),
			strconv.Itoa(s.PatientID),
			s.Status,
			aiLabel,
			aiConf,
			aiRisk,
			diagNotes,
			s.ImageURL,
			s.CreatedAt.Format("2006-01-02 15:04:05"),
		})
	}
}

func (h *ExportHandler) ExportPatients(c *gin.Context) {
	patients, err := h.UserRepo.GetAllPatients()
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch patients", err.Error())
		return
	}

	c.Header("Content-Type", "text/csv")
	c.Header("Content-Disposition", "attachment;filename=patients_export.csv")

	writer := csv.NewWriter(c.Writer)
	defer writer.Flush()

	// Header updated with medical details
	writer.Write([]string{"ID", "Patient Name", "Phone", "Gender", "DOB", "Medical History", "Allergies", "Emergency Contact"})

	for _, p := range patients {
		name := "N/A"
		if p.User != nil {
			name = p.User.FullName
		}
		writer.Write([]string{
			strconv.Itoa(p.ID),
			name,
			p.Phone,
			p.Gender,
			p.DOB,
			p.MedicalHistory,
			p.Allergies,
			p.EmergencyContact,
		})
	}
}

func (h *ExportHandler) ExportUsers(c *gin.Context) {
	users, err := h.UserRepo.GetAllUsers()
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch users", err.Error())
		return
	}

	c.Header("Content-Type", "text/csv")
	c.Header("Content-Disposition", "attachment;filename=users_export.csv")

	writer := csv.NewWriter(c.Writer)
	defer writer.Flush()

	// Header
	writer.Write([]string{"ID", "Username", "FullName", "Role", "CreatedAt"})

	for _, u := range users {
		writer.Write([]string{
			strconv.Itoa(u.ID),
			u.Username,
			u.FullName,
			u.RoleName,
			u.CreatedAt.Format("2006-01-02 15:04:05"),
		})
	}
}

// GET /export/patient/:id — Export a single patient's full medical report
func (h *ExportHandler) ExportSinglePatient(c *gin.Context) {
	patientID, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid patient ID", nil)
		return
	}

	// Get patient info
	var name, phone, gender, dob, medHistory, allergies, emergencyContact string
	err = h.UserRepo.DB.QueryRow(`
		SELECT u.full_name, IFNULL(p.phone,''), IFNULL(p.gender,''), IFNULL(p.dob,''),
		       IFNULL(p.medical_history,''), IFNULL(p.allergies,''), IFNULL(p.emergency_contact,'')
		FROM patients p
		JOIN users u ON p.user_id = u.id
		WHERE p.id = ?`, patientID).Scan(&name, &phone, &gender, &dob, &medHistory, &allergies, &emergencyContact)
	if err != nil {
		utils.ErrorResponse(c, http.StatusNotFound, "Patient not found", nil)
		return
	}

	// Get scans
	scans, _ := h.ScanRepo.GetScansByPatient(patientID)
	if scans == nil {
		scans = []model.CTScan{}
	}

	// Get health records
	records, _ := h.RecordRepo.GetByPatientID(patientID)
	if records == nil {
		records = []model.HealthRecord{}
	}

	filename := fmt.Sprintf("patient_%d_%s.csv", patientID, name)
	c.Header("Content-Type", "text/csv")
	c.Header("Content-Disposition", fmt.Sprintf("attachment;filename=%s", filename))

	writer := csv.NewWriter(c.Writer)
	defer writer.Flush()

	// Section 1: Patient Profile
	writer.Write([]string{"=== PATIENT PROFILE ==="})
	writer.Write([]string{"Field", "Value"})
	writer.Write([]string{"Patient ID", strconv.Itoa(patientID)})
	writer.Write([]string{"Name", name})
	writer.Write([]string{"Phone", phone})
	writer.Write([]string{"Gender", gender})
	writer.Write([]string{"Date of Birth", dob})
	writer.Write([]string{"Medical History", medHistory})
	writer.Write([]string{"Allergies", allergies})
	writer.Write([]string{"Emergency Contact", emergencyContact})
	writer.Write([]string{""})

	// Section 2: CT Scans
	writer.Write([]string{"=== CT SCANS ==="})
	writer.Write([]string{"Scan ID", "Status", "AI Prediction", "Confidence", "Risk Level", "Doctor Diagnosis", "Date"})
	for _, s := range scans {
		aiLabel, aiConf, aiRisk, diagNotes := "N/A", "N/A", "N/A", "N/A"
		if s.AIResult != nil {
			aiLabel = s.AIResult.PredictionLabel
			aiConf = strconv.FormatFloat(s.AIResult.Confidence, 'f', 2, 64)
			aiRisk = s.AIResult.RiskLevel
		}
		if s.Diagnosis != nil {
			diagNotes = s.Diagnosis.Notes
		}
		writer.Write([]string{
			strconv.Itoa(s.ID), s.Status, aiLabel, aiConf, aiRisk, diagNotes,
			s.CreatedAt.Format("2006-01-02 15:04:05"),
		})
	}
	writer.Write([]string{""})

	// Section 3: Health Records
	writer.Write([]string{"=== HEALTH RECORDS ==="})
	writer.Write([]string{"Record ID", "Systolic", "Diastolic", "Heart Rate", "Temperature", "SpO2", "Weight", "Health Score", "Alert", "Notes", "Date"})
	for _, r := range records {
		writer.Write([]string{
			strconv.Itoa(r.ID),
			strconv.Itoa(r.Systolic),
			strconv.Itoa(r.Diastolic),
			strconv.Itoa(r.HeartRate),
			fmt.Sprintf("%.1f", r.Temperature),
			strconv.Itoa(r.OxygenLevel),
			fmt.Sprintf("%.1f", r.Weight),
			strconv.Itoa(r.HealthScore),
			r.AlertStatus,
			r.Notes,
			r.CreatedAt.Format("2006-01-02 15:04:05"),
		})
	}
}

// GET /export/doctor/patients — Export all patients assigned to the logged-in doctor
func (h *ExportHandler) ExportDoctorPatients(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	// Get all patient IDs assigned to this doctor
	rows, err := h.UserRepo.DB.Query(`
		SELECT DISTINCT p.id, u.full_name, IFNULL(p.phone,''), IFNULL(p.gender,''), IFNULL(p.dob,''),
		       IFNULL(p.medical_history,''), IFNULL(p.allergies,''), IFNULL(p.emergency_contact,'')
		FROM patients p
		JOIN users u ON p.user_id = u.id
		WHERE p.id IN (
			SELECT patient_id FROM ct_scans WHERE doctor_id = ?
			UNION
			SELECT patient_id FROM appointments WHERE doctor_id = ?
		)
	`, user.ID, user.ID)
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch patients", err.Error())
		return
	}
	defer rows.Close()

	c.Header("Content-Type", "text/csv")
	c.Header("Content-Disposition", "attachment;filename=my_patients_export.csv")

	writer := csv.NewWriter(c.Writer)
	defer writer.Flush()

	writer.Write([]string{"Patient ID", "Name", "Phone", "Gender", "DOB", "Medical History", "Allergies", "Emergency Contact"})

	for rows.Next() {
		var id int
		var pName, pPhone, pGender, pDob, pMedHistory, pAllergies, pEmergency string
		if err := rows.Scan(&id, &pName, &pPhone, &pGender, &pDob, &pMedHistory, &pAllergies, &pEmergency); err != nil {
			continue
		}
		writer.Write([]string{
			strconv.Itoa(id), pName, pPhone, pGender, pDob, pMedHistory, pAllergies, pEmergency,
		})
	}
}

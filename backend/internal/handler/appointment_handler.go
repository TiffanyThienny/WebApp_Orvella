package handler

import (
	"net/http"
	"strconv"
	"time"

	"backend/internal/model"
	"backend/internal/repository"
	"backend/pkg/utils"

	"github.com/gin-gonic/gin"
)

type AppointmentHandler struct {
	Repo         *repository.AppointmentRepository
	UserRepo     *repository.UserRepository
	ScheduleRepo *repository.ScheduleRepository
}

func NewAppointmentHandler(r *repository.AppointmentRepository, ur *repository.UserRepository, sr *repository.ScheduleRepository) *AppointmentHandler {
	return &AppointmentHandler{Repo: r, UserRepo: ur, ScheduleRepo: sr}
}

// POST /appointments
func (h *AppointmentHandler) Create(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	if user.RoleID != 3 {
		c.JSON(http.StatusForbidden, gin.H{"error": "Only patients can book appointments"})
		return
	}

	patient, err := h.UserRepo.FindPatientByUserID(user.ID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Patient profile not found"})
		return
	}

	var req struct {
		DoctorID        int    `json:"doctor_id" binding:"required"`
		AppointmentDate string `json:"appointment_date" binding:"required"`
		Notes           string `json:"notes"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	parsedDate, err := time.Parse(time.RFC3339, req.AppointmentDate)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid date format, use ISO string (RFC3339)"})
		return
	}

	if parsedDate.Before(time.Now()) {
		utils.ErrorResponse(c, http.StatusBadRequest, "Appointment date must be in the future", nil)
		return
	}

	// Validate against doctor's schedule
	schedules, err := h.ScheduleRepo.GetByDoctorID(req.DoctorID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to fetch doctor schedules"})
		return
	}

	// Use local Jakarta time (UTC+7) for date/day matching since schedules are stored in local time
	jakartaLoc := time.FixedZone("WIB", 7*60*60)
	parsedLocal := parsedDate.In(jakartaLoc)
	reqDateStr := parsedLocal.Format("2006-01-02")

	var matchedSchedule *model.Schedule
	for _, s := range schedules {
		if !s.IsAvailable {
			continue
		}

		match := false
		if s.AppointmentDate != nil && *s.AppointmentDate != "" {
			// Specific date schedule: check date range
			startStr := (*s.AppointmentDate)[:10]
			endStr := startStr
			if s.EndDate != nil && *s.EndDate != "" {
				endStr = (*s.EndDate)[:10]
			}
			if reqDateStr >= startStr && reqDateStr <= endStr {
				match = true
			}
		} else if s.DayOfWeek != "" {
			// Recurring schedule: check day of week
			if s.DayOfWeek == parsedLocal.Weekday().String() {
				match = true
			}
		}

		if match {
			matchedSchedule = &s
			break
		}
	}

	if matchedSchedule == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "No available schedule found for the selected date. Please choose a date when the doctor is available."})
		return
	}

	// Check max patients limit dynamically
	dailyCount, err := h.Repo.GetDailyCount(req.DoctorID, reqDateStr)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to verify daily schedule limit"})
		return
	}

	if dailyCount >= matchedSchedule.MaxPatients {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Daily slot full. Please choose another date."})
		return
	}

	app := &model.Appointment{
		PatientID:       patient.ID,
		DoctorID:        req.DoctorID,
		AppointmentDate: parsedDate,
		Notes:           req.Notes,
	}

	if err := h.Repo.Create(app); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to book appointment", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusCreated, "Appointment booked successfully", app)
}

// GET /appointments
func (h *AppointmentHandler) Get(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	var appointments []model.Appointment
	var err error

	if user.RoleID == 3 {
		patient, perr := h.UserRepo.FindPatientByUserID(user.ID)
		if perr != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Patient profile not found"})
			return
		}
		appointments, err = h.Repo.GetByPatientID(patient.ID)
	} else if user.RoleID == 2 {
		appointments, err = h.Repo.GetByDoctorID(user.ID)
	} else {
		c.JSON(http.StatusForbidden, gin.H{"error": "Unauthorized access"})
		return
	}

	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch appointments", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Appointments fetched successfully", appointments)
}

// PUT /appointments/:id/status
func (h *AppointmentHandler) UpdateStatus(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	if user.RoleID != 2 {
		c.JSON(http.StatusForbidden, gin.H{"error": "Only doctors can update appointment status"})
		return
	}

	id, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid ID"})
		return
	}

	var req struct {
		Status string `json:"status" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := h.Repo.UpdateStatus(id, req.Status); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to update status", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Appointment status updated", nil)
}

// PUT /appointments/:id/cancel
func (h *AppointmentHandler) Cancel(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	if user.RoleID != 3 {
		c.JSON(http.StatusForbidden, gin.H{"error": "Only patients can cancel appointments"})
		return
	}

	appID, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid appointment ID"})
		return
	}

	patient, err := h.UserRepo.FindPatientByUserID(user.ID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Patient profile not found"})
		return
	}

	app, err := h.Repo.GetByID(appID)
	if err != nil || app.PatientID != patient.ID {
		c.JSON(http.StatusNotFound, gin.H{"error": "Appointment not found or unauthorized"})
		return
	}

	if err := h.Repo.UpdateStatus(appID, "cancelled"); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to cancel appointment", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Appointment cancelled successfully", nil)
}

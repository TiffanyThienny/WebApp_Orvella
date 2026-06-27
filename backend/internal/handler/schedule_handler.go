package handler

import (
	"fmt"
	"net/http"
	"strconv"
	"strings"
	"time"

	"backend/internal/model"
	"backend/internal/repository"
	"backend/pkg/utils"

	"github.com/gin-gonic/gin"
)

type ScheduleHandler struct {
	Repo *repository.ScheduleRepository
}

func NewScheduleHandler(r *repository.ScheduleRepository) *ScheduleHandler {
	return &ScheduleHandler{Repo: r}
}

// POST /admin/schedules
func (h *ScheduleHandler) AssignSchedule(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	if user.RoleID != 1 {
		utils.ErrorResponse(c, http.StatusForbidden, "Only admins can assign schedules", nil)
		return
	}

	var req []struct {
		DoctorID        int     `json:"doctor_id"`
		DayOfWeek       string  `json:"day_of_week"`
		AppointmentDate *string `json:"appointment_date"`
		EndDate         *string `json:"end_date"`
		StartTime       string  `json:"start_time"`
		EndTime         string  `json:"end_time"`
		MaxPatients     int     `json:"max_patients"`
		IsAvailable     bool    `json:"is_available"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request body", err.Error())
		return
	}

	today := time.Now().Truncate(24 * time.Hour)

	for _, s := range req {
		// Backend validation: reject past dates
		if s.AppointmentDate != nil && *s.AppointmentDate != "" {
			parsedDate, err := time.Parse("2006-01-02", *s.AppointmentDate)
			if err != nil {
				utils.ErrorResponse(c, http.StatusBadRequest, fmt.Sprintf("Invalid date format for appointment_date: %s", *s.AppointmentDate), nil)
				return
			}
			if parsedDate.Before(today) {
				utils.ErrorResponse(c, http.StatusBadRequest, fmt.Sprintf("Schedule date %s is in the past. Only future dates are allowed.", *s.AppointmentDate), nil)
				return
			}
		}

		// Backend validation: end time must be after start time
		if s.StartTime != "" && s.EndTime != "" {
			start, err1 := time.Parse("15:04", strings.TrimSuffix(s.StartTime, ":00"))
			end, err2 := time.Parse("15:04", strings.TrimSuffix(s.EndTime, ":00"))
			if err1 != nil || err2 != nil {
				// Try with seconds
				start, err1 = time.Parse("15:04:05", s.StartTime)
				end, err2 = time.Parse("15:04:05", s.EndTime)
			}
			if err1 == nil && err2 == nil {
				if !end.After(start) {
					utils.ErrorResponse(c, http.StatusBadRequest, "End time must be after start time", nil)
					return
				}
			}
		}

		sched := &model.Schedule{
			DoctorID:        s.DoctorID,
			DayOfWeek:       s.DayOfWeek,
			AppointmentDate: s.AppointmentDate,
			EndDate:         s.EndDate,
			StartTime:       s.StartTime,
			EndTime:         s.EndTime,
			MaxPatients:     s.MaxPatients,
			IsAvailable:     s.IsAvailable,
		}
		if err := h.Repo.CreateOrUpdate(sched); err != nil {
			utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to save schedule", err.Error())
			return
		}
	}

	utils.SuccessResponse(c, http.StatusOK, "Schedules saved successfully", nil)
}

// PUT /admin/schedules/:id
func (h *ScheduleHandler) UpdateSchedule(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	if user.RoleID != 1 {
		utils.ErrorResponse(c, http.StatusForbidden, "Only admins can update schedules", nil)
		return
	}

	id, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid schedule ID", nil)
		return
	}

	var req struct {
		DoctorID        int     `json:"doctor_id"`
		DayOfWeek       string  `json:"day_of_week"`
		AppointmentDate *string `json:"appointment_date"`
		EndDate         *string `json:"end_date"`
		StartTime       string  `json:"start_time"`
		EndTime         string  `json:"end_time"`
		MaxPatients     int     `json:"max_patients"`
		IsAvailable     bool    `json:"is_available"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request body", err.Error())
		return
	}

	sched := &model.Schedule{
		DoctorID:        req.DoctorID,
		DayOfWeek:       req.DayOfWeek,
		AppointmentDate: req.AppointmentDate,
		EndDate:         req.EndDate,
		StartTime:       req.StartTime,
		EndTime:         req.EndTime,
		MaxPatients:     req.MaxPatients,
		IsAvailable:     req.IsAvailable,
	}

	if err := h.Repo.Update(id, sched); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to update schedule", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Schedule updated successfully", nil)
}

// timesOverlap checks if two time ranges overlap (all times as "HH:MM:SS" or "HH:MM")
func timesOverlap(start1, end1, start2, end2 string) bool {
	// Simple string comparison works for HH:MM:SS format
	return start1 < end2 && start2 < end1
}

// GET /schedules
func (h *ScheduleHandler) GetAllSchedules(c *gin.Context) {
	schedules, err := h.Repo.GetAll()
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch schedules", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Schedules fetched successfully", schedules)
}

// GET /schedules/:doctor_id
func (h *ScheduleHandler) GetDoctorSchedules(c *gin.Context) {
	doctorID, err := strconv.Atoi(c.Param("doctor_id"))
	if err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid doctor ID", nil)
		return
	}

	schedules, err := h.Repo.GetByDoctorID(doctorID)
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch schedules", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Doctor schedules fetched successfully", schedules)
}

// DELETE /admin/schedules/:id
func (h *ScheduleHandler) DeleteSchedule(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	if user.RoleID != 1 {
		utils.ErrorResponse(c, http.StatusForbidden, "Only admins can delete schedules", nil)
		return
	}

	id, err := strconv.Atoi(c.Param("id"))
	if err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid ID", nil)
		return
	}

	if err := h.Repo.Delete(id); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to delete schedule", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Schedule deleted successfully", nil)
}

 package handler

import (
	"net/http"
	"regexp"

	"backend/internal/model"
	"backend/internal/service"
	"backend/pkg/utils"

	"github.com/gin-gonic/gin"
)

type AuthHandler struct {
	Service *service.AuthService
}

func NewAuthHandler(s *service.AuthService) *AuthHandler {
	return &AuthHandler{Service: s}
}

func (h *AuthHandler) Register(c *gin.Context) {
	var req struct {
		RoleID           int    `json:"role_id" binding:"required"`
		Username         string `json:"username" binding:"required"`
		Email            string `json:"email" binding:"required"`
		Password         string `json:"password" binding:"required"`
		FullName         string `json:"full_name" binding:"required"`
		Phone            string `json:"phone"`
		Address          string `json:"address"`
		DOB              string `json:"date_of_birth"`
		Gender           string `json:"gender"`
		MedicalHistory   string `json:"medical_history"`
		Allergies        string `json:"allergies"`
		EmergencyContact string `json:"emergency_contact"`
		Specialty        string `json:"specialty"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request body", err.Error())
		return
	}

	if req.RoleID == 3 {
		if req.DOB == "" || req.Gender == "" || req.Phone == "" || req.Address == "" || req.EmergencyContact == "" {
			utils.ErrorResponse(c, http.StatusBadRequest, "For patient registration, fields (Date of Birth, Gender, Phone, Address, and Emergency Contact) are required.", nil)
			return
		}
	}

	// Phone Validation
	if req.Phone != "" {
		if matched, _ := regexp.MatchString(`^[0-9]+$`, req.Phone); !matched {
			utils.ErrorResponse(c, http.StatusBadRequest, "Nomor telepon harus berupa angka saja", nil)
			return
		}
	}
	if req.EmergencyContact != "" {
		if matched, _ := regexp.MatchString(`^[0-9]+$`, req.EmergencyContact); !matched {
			utils.ErrorResponse(c, http.StatusBadRequest, "Kontak darurat harus berupa angka saja", nil)
			return
		}
	}

	user := &model.User{
		RoleID:   req.RoleID,
		Username: req.Username,
		Email:    req.Email,
		FullName:  req.FullName,
		Phone:     req.Phone,
		Address:   req.Address,
		Specialty: req.Specialty,
	}

	var profile *model.Patient
	if req.RoleID == 3 {
		profile = &model.Patient{
			DOB:              req.DOB,
			Gender:           req.Gender,
			MedicalHistory:   req.MedicalHistory,
			Allergies:        req.Allergies,
			EmergencyContact: req.EmergencyContact,
		}
	}

	token, err := h.Service.Register(user, req.Password, profile)
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to create user", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "User registered successfully", gin.H{"token": token, "user": user})
}

func (h *AuthHandler) Login(c *gin.Context) {
	var req struct {
		Username string `json:"username" binding:"required"`
		Password string `json:"password" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request body", err.Error())
		return
	}

	token, err := h.Service.Login(req.Username, req.Password)
	if err != nil {
		utils.ErrorResponse(c, http.StatusUnauthorized, "Invalid credentials", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Login successful", gin.H{"token": token})
}

func (h *AuthHandler) Me(c *gin.Context) {
	userObj, exists := c.Get("user")
	if !exists {
		utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized", nil)
		return
	}
	user := userObj.(model.User)
	utils.SuccessResponse(c, http.StatusOK, "User fetched successfully", gin.H{"user": user})
}

func (h *AuthHandler) Logout(c *gin.Context) {
	userObj, exists := c.Get("user")
	if !exists {
		utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized", nil)
		return
	}
	user := userObj.(model.User)

	if err := h.Service.Logout(user.ID); err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to logout", err.Error())
		return
	}

	utils.SuccessResponse(c, http.StatusOK, "Logged out successfully", nil)
}

func (h *AuthHandler) ForgotPassword(c *gin.Context) {
	var req struct {
		Username string `json:"username" binding:"required"`
		Password string `json:"password"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Username required", nil)
		return
	}

	user, err := h.Service.CheckUserExists(req.Username)
	if err != nil {
		utils.ErrorResponse(c, http.StatusNotFound, "User with this username not found", nil)
		return
	}

	// If password is provided, perform a direct reset (as requested by user)
	if req.Password != "" {
		err = h.Service.ResetPassword(user.ID, req.Password)
		if err != nil {
			utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to reset password", err.Error())
			return
		}
		utils.SuccessResponse(c, http.StatusOK, "Password updated successfully. Access node "+req.Username+" restored.", nil)
		return
	}

	// Otherwise, return success for identity verification step
	utils.SuccessResponse(c, http.StatusOK, "User verified: "+user.Email, gin.H{"step": "verified"})
}

func (h *AuthHandler) GetUsers(c *gin.Context) {
	users, err := h.Service.GetAllUsers()
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch users", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Users fetched successfully", users)
}

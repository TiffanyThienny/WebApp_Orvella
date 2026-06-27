package handler

import (
	"database/sql"
	"log"
	"net/http"
	"regexp"
	"strconv"

	"backend/internal/model"
	"backend/internal/repository"
	"backend/pkg/utils"

	"github.com/gin-gonic/gin"
	"golang.org/x/crypto/bcrypt"
)

type UserHandler struct {
	UserRepo  *repository.UserRepository
	ScanRepo  *repository.ScanRepository
	RecordRepo *repository.RecordRepository
}

func NewUserHandler(u *repository.UserRepository, s *repository.ScanRepository, r *repository.RecordRepository) *UserHandler {
	return &UserHandler{UserRepo: u, ScanRepo: s, RecordRepo: r}
}

// GET /profile
func (h *UserHandler) GetProfile(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	profile, err := h.UserRepo.FindByUsername(user.Username)
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch profile", err.Error())
		return
	}
	utils.SuccessResponse(c, http.StatusOK, "Profile fetched successfully", profile)
}

// PUT /profile
func (h *UserHandler) UpdateProfile(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	var req struct {
		FullName string `json:"full_name"`
		Username string `json:"username"`
		Phone    string `json:"phone"`
		Address  string `json:"address"`
		Password string `json:"password"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Phone Validation
	if req.Phone != "" {
		if matched, _ := regexp.MatchString(`^[0-9]+$`, req.Phone); !matched {
			utils.ErrorResponse(c, http.StatusBadRequest, "Nomor telepon harus berupa angka saja", nil)
			return
		}
	}

	// Check if username is already taken by another user
	if req.Username != "" && req.Username != user.Username {
		var exists bool
		err := h.UserRepo.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM users WHERE username=? AND id!=?)", req.Username, user.ID).Scan(&exists)
		if err == nil && exists {
			c.JSON(http.StatusBadRequest, gin.H{"error": "Username already taken"})
			return
		}
	}

	tx, err := h.UserRepo.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Transaction failed"})
		return
	}

	updateQuery := "UPDATE users SET full_name=?, phone=?, address=?"
	args := []interface{}{req.FullName, req.Phone, req.Address}

	if req.Username != "" {
		updateQuery += ", username=?"
		args = append(args, req.Username)
	}

	if req.Password != "" {
		hashed, err := bcrypt.GenerateFromPassword([]byte(req.Password), bcrypt.DefaultCost)
		if err == nil {
			updateQuery += ", password_hash=?"
			args = append(args, string(hashed))
		}
	}

	updateQuery += " WHERE id=?"
	args = append(args, user.ID)

	_, err = tx.Exec(updateQuery, args...)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Update failed"})
		return
	}

	// Also sync patient table if patient
	if user.RoleID == 3 {
		_, _ = tx.Exec("UPDATE patients SET phone=?, address=? WHERE user_id=?", req.Phone, req.Address, user.ID)
	}

	tx.Commit()
	utils.SuccessResponse(c, http.StatusOK, "Profile updated", nil)
}

// PUT /profile/complete
func (h *UserHandler) CompleteProfile(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	if user.RoleID != 3 {
		c.JSON(http.StatusForbidden, gin.H{"error": "Only patients can complete this profile form"})
		return
	}

	var req struct {
		FullName         string `json:"full_name"`
		Phone            string `json:"phone_number"`
		Address          string `json:"address"`
		DOB              string `json:"date_of_birth"`
		Gender           string `json:"gender"`
		MedicalHistory   string `json:"medical_history"`
		Allergies        string `json:"allergies"`
		EmergencyContact string `json:"emergency_contact"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Phone validation
	if req.Phone != "" {
		if matched, _ := regexp.MatchString(`^[0-9]+$`, req.Phone); !matched {
			utils.ErrorResponse(c, http.StatusBadRequest, "Nomor telepon harus berupa angka saja", nil)
			return
		}
	}
	if req.EmergencyContact != "" {
		if matched, _ := regexp.MatchString(`^[0-9]+$`, req.EmergencyContact); !matched {
			c.JSON(http.StatusBadRequest, gin.H{"error": "Kontak darurat harus berupa angka saja"})
			return
		}
	}

	// Update users table
	_, err := h.UserRepo.DB.Exec("UPDATE users SET full_name=?, phone=?, address=?, is_profile_complete=1 WHERE id=?", 
		req.FullName, req.Phone, req.Address, user.ID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "User update failed"})
		return
	}

	// Update patients table
	_, err = h.UserRepo.DB.Exec("UPDATE patients SET dob=?, gender=?, phone=?, address=?, medical_history=?, allergies=?, emergency_contact=? WHERE user_id=?", 
		req.DOB, req.Gender, req.Phone, req.Address, req.MedicalHistory, req.Allergies, req.EmergencyContact, user.ID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Patient profile update failed"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Profile completed successfully"})
}

// GET /patients
func (h *UserHandler) GetPatients(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	log.Printf("[SYSTEM] Fetching patients for user %s (Role: %s)", user.Username, user.RoleName)

	query := `
		SELECT 
			p.id, p.user_id, u.full_name, 
			IFNULL(p.dob, ''), IFNULL(u.phone, ''), 
			IFNULL(u.profile_image, ''),
			IFNULL(hr.alert_status, 'normal') as status,
			IFNULL(p.gender, ''),
			IFNULL(p.emergency_contact, '')
		FROM users u
		JOIN patients p ON p.user_id = u.id
		LEFT JOIN (
			SELECT patient_id, alert_status
			FROM health_records
			WHERE id IN (SELECT MAX(id) FROM health_records GROUP BY patient_id)
		) hr ON hr.patient_id = p.id
	`
	args := []interface{}{}

	if user.RoleName == "Doctor" {
		query += ` 
			WHERE p.id IN (
				SELECT patient_id FROM ct_scans WHERE doctor_id = ?
				UNION
				SELECT patient_id FROM appointments WHERE doctor_id = ?
			)
		`
		args = append(args, user.ID, user.ID)
	}

	rows, err := h.UserRepo.DB.Query(query, args...)
	if err != nil {
		log.Printf("[CRITICAL ERROR] GetPatients query failed: %v", err)
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Database sync failed: " + err.Error()})
		return
	}
	defer rows.Close()

	var patients []map[string]interface{}
	for rows.Next() {
		var id, userID int
		var name, dob, phone, img, status, gender, emergencyContact string
		if err := rows.Scan(&id, &userID, &name, &dob, &phone, &img, &status, &gender, &emergencyContact); err != nil {
			log.Printf("[ERROR] Scan row failed in GetPatients: %v", err)
			continue
		}

		patients = append(patients, map[string]interface{}{
			"id":                id,
			"user_id":           userID,
			"name":              name,
			"dob":               dob,
			"phone":             phone,
			"profile_image":     img,
			"status":            status,
			"gender":            gender,
			"emergency_contact": emergencyContact,
		})
	}
	
	if patients == nil {
		patients = []map[string]interface{}{}
	}
	
	utils.SuccessResponse(c, http.StatusOK, "Patients fetched successfully", patients)
}

// GET /patients/:id
func (h *UserHandler) GetPatientDetail(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))

	var patient struct {
		ID             int    `json:"id"`
		UserID         int    `json:"user_id"`
		Name           string `json:"name"`
		Phone          string `json:"phone"`
		Address        string `json:"address"`
		DOB            string `json:"dob"`
		Gender         string `json:"gender"`
		MedicalHistory string `json:"medical_history"`
		Allergies      string `json:"allergies"`
	}

	var userID, roleID int
	var fullName, phone, address, dob, gender, medHistory, allergies string
	
	err := h.UserRepo.DB.QueryRow(`
		SELECT 
			p.id, u.id as user_id, u.role_id, u.full_name, IFNULL(u.phone, ''), 
			IFNULL(p.address, ''), IFNULL(p.dob, ''), IFNULL(p.gender, ''),
			IFNULL(p.medical_history, ''), IFNULL(p.allergies, '')
		FROM patients p
		JOIN users u ON p.user_id = u.id
		WHERE p.id=?`, id).Scan(
		&patient.ID, &userID, &roleID, &fullName, &phone, &address, &dob, &gender, &medHistory, &allergies,
	)

	if err != nil && err != sql.ErrNoRows {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Query failed: " + err.Error()})
		return
	}

	if err == sql.ErrNoRows {
		c.JSON(http.StatusNotFound, gin.H{"error": "Clinical record not found."})
		return
	}

	// Populate struct
	patient.UserID = userID
	patient.Name = fullName
	patient.Phone = phone
	patient.Address = address
	patient.DOB = dob
	patient.Gender = gender
	patient.MedicalHistory = medHistory
	patient.Allergies = allergies

	scans, err := h.ScanRepo.GetScansByPatient(patient.ID)
	if err != nil || scans == nil {
		scans = []model.CTScan{}
	}

	records, err := h.RecordRepo.GetByPatientID(patient.ID)
	if err != nil || records == nil {
		records = []model.HealthRecord{}
	}

	utils.SuccessResponse(c, http.StatusOK, "Patient details fetched", map[string]interface{}{
		"profile": patient,
		"scans":   scans,
		"records": records,
	})
}

// PUT /patients/:id
func (h *UserHandler) UpdatePatientDetail(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))

	var req struct {
		Name           string `json:"name"`
		Phone          string `json:"phone"`
		Address        string `json:"address"`
		DOB            string `json:"dob"`
		MedicalHistory string `json:"medical_history"`
		Allergies      string `json:"allergies"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	tx, err := h.UserRepo.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Transaction failed"})
		return
	}

	// 1. Get UserID from patient id
	var userID int
	err = tx.QueryRow("SELECT user_id FROM patients WHERE id=?", id).Scan(&userID)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusNotFound, gin.H{"error": "Patient not found"})
		return
	}

	// 2. Update Users table
	_, err = tx.Exec("UPDATE users SET full_name=?, phone=?, address=? WHERE id=?", req.Name, req.Phone, req.Address, userID)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to update user identity"})
		return
	}

	// 3. Update Patients table
	_, err = tx.Exec("UPDATE patients SET dob=?, phone=?, address=?, medical_history=?, allergies=? WHERE id=?", 
		req.DOB, req.Phone, req.Address, req.MedicalHistory, req.Allergies, id)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to update clinical profile"})
		return
	}

	tx.Commit()
	utils.SuccessResponse(c, http.StatusOK, "Clinical record updated successfully", nil)
}

func (h *UserHandler) GetDoctorStats(c *gin.Context) {
	userObj, _ := c.Get("user")
	user := userObj.(model.User)

	log.Printf("[SYSTEM] Fetching analytics for Dr. %s", user.Username)

	statusStats := map[string]int{
		"pending":  0,
		"analyzed": 0,
		"reviewed": 0,
		"approved": 0,
	}
	totalPatients := 0

	if user.RoleName != "Doctor" {
		c.JSON(http.StatusOK, gin.H{
			"data": map[string]interface{}{
				"total_patients": 0,
				"status_stats": statusStats,
			},
		})
		return
	}

	// 1. Total Patients Handled
	h.UserRepo.DB.QueryRow(`SELECT COUNT(DISTINCT patient_id) FROM ct_scans WHERE doctor_id=?`, user.ID).Scan(&totalPatients)

	// 2. Scan Status Distribution
	rows, err := h.UserRepo.DB.Query(`SELECT status, COUNT(*) FROM ct_scans WHERE doctor_id=? GROUP BY status`, user.ID)
	if err == nil {
		defer rows.Close()
		for rows.Next() {
			var s string
			var count int
			rows.Scan(&s, &count)
			if _, ok := statusStats[s]; ok {
				statusStats[s] = count
			}
		}
	}

	// 3. Reviewed Today
	var reviewedToday int
	h.UserRepo.DB.QueryRow(`
		SELECT COUNT(*) 
		FROM health_records 
		WHERE created_by = ? AND DATE(created_at) = CURDATE()
	`, user.ID).Scan(&reviewedToday)

	// 4. Avg Review Time
	var avgReviewTimeMins float64
	h.UserRepo.DB.QueryRow(`
		SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, s.created_at, r.created_at)), 0)
		FROM ct_scans s
		JOIN health_records r ON s.id = r.scan_id
		WHERE s.doctor_id = ? AND s.status IN ('approved', 'rejected')
	`, user.ID).Scan(&avgReviewTimeMins)

	utils.SuccessResponse(c, http.StatusOK, "Doctor stats fetched", map[string]interface{}{
		"total_patients":        totalPatients,
		"status_stats":          statusStats,
		"reviewed_today":        reviewedToday,
		"avg_review_time_mins": avgReviewTimeMins,
	})
}

// GET /analytics/admin
func (h *UserHandler) GetAdminAnalytics(c *gin.Context) {
	roleRows, err := h.UserRepo.DB.Query(`
		SELECT r.name, COUNT(u.id) 
		FROM users u 
		JOIN roles r ON u.role_id = r.id 
		GROUP BY r.name
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to fetch role distribution"})
		return
	}
	defer roleRows.Close()

	var roleDistribution []map[string]interface{}
	for roleRows.Next() {
		var name string
		var count int
		roleRows.Scan(&name, &count)
		roleDistribution = append(roleDistribution, map[string]interface{}{
			"name": name,
			"value": count,
		})
	}

	growthRows, err := h.UserRepo.DB.Query(`
		SELECT DATE(created_at) as date, COUNT(id) as count 
		FROM users 
		WHERE role_id = 3 
		GROUP BY DATE(created_at) 
		ORDER BY date ASC
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to fetch patient growth"})
		return
	}
	defer growthRows.Close()

	var patientGrowth []map[string]interface{}
	for growthRows.Next() {
		var date string
		var count int
		growthRows.Scan(&date, &count)
		patientGrowth = append(patientGrowth, map[string]interface{}{
			"date": date,
			"patients": count,
		})
	}

	var totalUsers, totalScans, totalRecords int
	h.UserRepo.DB.QueryRow("SELECT COUNT(*) FROM users").Scan(&totalUsers)
	h.UserRepo.DB.QueryRow("SELECT COUNT(*) FROM ct_scans").Scan(&totalScans)
	h.UserRepo.DB.QueryRow("SELECT COUNT(*) FROM health_records").Scan(&totalRecords)

	utils.SuccessResponse(c, http.StatusOK, "Admin analytics fetched", map[string]interface{}{
		"role_distribution": roleDistribution,
		"patient_growth": patientGrowth,
		"system_stats": map[string]int{
			"total_users": totalUsers,
			"total_scans": totalScans,
			"total_records": totalRecords,
		},
	})
}

// GET /doctors
func (h *UserHandler) GetDoctors(c *gin.Context) {
	rows, err := h.UserRepo.DB.Query(`
		SELECT id, full_name, username, profile_image, IFNULL(specialty, '') 
		FROM users 
		WHERE role_id = 2
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to fetch doctors"})
		return
	}
	defer rows.Close()

	var doctors []map[string]interface{}
	for rows.Next() {
		var id int
		var fullName, username, specialty string
		var profileImg *string
		rows.Scan(&id, &fullName, &username, &profileImg, &specialty)

		img := ""
		if profileImg != nil {
			img = *profileImg
		}

		doctors = append(doctors, map[string]interface{}{
			"id": id,
			"full_name": fullName,
			"username": username,
			"profile_image": img,
			"specialty": specialty,
		})
	}
	utils.SuccessResponse(c, http.StatusOK, "Doctors fetched successfully", doctors)
}

// PUT /users/:id (Admin only)
func (h *UserHandler) UpdateUser(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))

	var req struct {
		FullName  string `json:"full_name"`
		Username  string `json:"username"`
		Phone     string `json:"phone"`
		Address   string `json:"address"`
		Email     string `json:"email"`
		Password  string `json:"password"`
		Specialty string `json:"specialty"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 1. Check if user exists and get role
	var roleID int
	err := h.UserRepo.DB.QueryRow("SELECT role_id FROM users WHERE id = ?", id).Scan(&roleID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "User not found"})
		return
	}

	// Check if username is already taken by another user
	if req.Username != "" {
		var exists bool
		err = h.UserRepo.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM users WHERE username=? AND id!=?)", req.Username, id).Scan(&exists)
		if err == nil && exists {
			c.JSON(http.StatusBadRequest, gin.H{"error": "Username already taken"})
			return
		}
	}

	tx, err := h.UserRepo.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Transaction failed"})
		return
	}

	// 2. Update users table (with dynamic fields for email/password/username)
	updateQuery := "UPDATE users SET full_name=?, username=?, phone=?, address=?, email=?"
	args := []interface{}{req.FullName, req.Username, req.Phone, req.Address, req.Email}

	if req.Password != "" {
		hashed, err := bcrypt.GenerateFromPassword([]byte(req.Password), bcrypt.DefaultCost)
		if err == nil {
			updateQuery += ", password_hash=?"
			args = append(args, string(hashed))
		}
	}
	updateQuery += " WHERE id=?"
	args = append(args, id)

	_, err = tx.Exec(updateQuery, args...)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to update user"})
		return
	}

	// 3. If patient, update patients table too
	if roleID == 3 {
		_, err = tx.Exec("UPDATE patients SET phone=?, address=? WHERE user_id=?", req.Phone, req.Address, id)
		if err != nil {
			tx.Rollback()
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to sync patient profile"})
			return
		}
	}

	// 4. If doctor, update specialty
	if roleID == 2 && req.Specialty != "" {
		_, err = tx.Exec("UPDATE users SET specialty=? WHERE id=?", req.Specialty, id)
		if err != nil {
			tx.Rollback()
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to update doctor specialty"})
			return
		}
	}

	// 5. Update Profile Complete Status Logic
	// Logic to check if profile is complete
	// For Patients: full_name, phone, address, and date_of_birth in patients table must exist
	// For Doctors: full_name, phone, address, specialty
	// Actually, let's just use the `CheckProfileCompletion` function if it exists, else we can do a quick check here.
	if roleID == 3 {
		var phone, address sql.NullString
		err = tx.QueryRow("SELECT phone, address FROM patients WHERE user_id=?", id).Scan(&phone, &address)
		if err == nil && phone.Valid && phone.String != "" && address.Valid && address.String != "" && req.FullName != "" {
			tx.Exec("UPDATE users SET is_profile_complete = 1 WHERE id = ?", id)
		}
	} else if roleID == 2 {
		if req.Phone != "" && req.Address != "" && req.Specialty != "" && req.FullName != "" {
			tx.Exec("UPDATE users SET is_profile_complete = 1 WHERE id = ?", id)
		}
	} else {
		if req.Phone != "" && req.Address != "" && req.FullName != "" {
			tx.Exec("UPDATE users SET is_profile_complete = 1 WHERE id = ?", id)
		}
	}

	tx.Commit()
	utils.SuccessResponse(c, http.StatusOK, "User updated successfully", nil)
}

// DELETE /users/:id (Admin only)
func (h *UserHandler) DeleteUser(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))

	// 1. Check if user exists and get role
	var roleID int
	err := h.UserRepo.DB.QueryRow("SELECT role_id FROM users WHERE id = ?", id).Scan(&roleID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "User not found"})
		return
	}

	tx, err := h.UserRepo.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Transaction failed"})
		return
	}

	// 2. If patient, delete from patients table first (to satisfy FK constraints)
	if roleID == 3 {
		_, err = tx.Exec("DELETE FROM patients WHERE user_id = ?", id)
		if err != nil {
			tx.Rollback()
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to delete patient profile"})
			return
		}
	}

	// 3. Delete from users table
	_, err = tx.Exec("DELETE FROM users WHERE id = ?", id)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to delete user"})
		return
	}

	tx.Commit()
	utils.SuccessResponse(c, http.StatusOK, "User deleted successfully", nil)
}



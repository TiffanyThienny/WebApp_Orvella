package main

import (
	"backend/config"
	"backend/pkg/utils"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	users := []struct {
		RoleID   int
		Username string
		FullName string
		Password string
	}{
		{1, "admin", "System Administrator", "admin123"},
		{2, "dr_tirta", "Dr. Tirta Spesialis Paru", "doctor123"},
		{4, "medrec_budi", "Budi Staff RM", "medrec123"},
	}

	for _, u := range users {
		hash, _ := utils.HashPassword(u.Password)
		_, err := db.Exec(`
			INSERT INTO users (role_id, username, password_hash, full_name, is_profile_complete) 
			VALUES (?, ?, ?, ?, true)
			ON DUPLICATE KEY UPDATE 
				password_hash = VALUES(password_hash),
				role_id = VALUES(role_id),
				full_name = VALUES(full_name)`,
			u.RoleID, u.Username, hash, u.FullName,
		)
		if err != nil {
			log.Printf("Error inserting %s: %v", u.Username, err)
		} else {
			log.Printf("Created user: %s", u.Username)
		}
	}

	patientHash, _ := utils.HashPassword("Thien1234")
	db.Exec(`
		INSERT INTO users (role_id, username, password_hash, full_name, is_profile_complete) 
		VALUES (3, 'Thienn', ?, 'Thienny Pasien', true)
		ON DUPLICATE KEY UPDATE 
			password_hash = VALUES(password_hash)`, patientHash)

	var patientUserID, medrecID, doctorID int64
	db.QueryRow("SELECT id FROM users WHERE username = 'Thienn'").Scan(&patientUserID)
	db.QueryRow("SELECT id FROM users WHERE username = 'medrec_budi'").Scan(&medrecID)
	db.QueryRow("SELECT id FROM users WHERE username = 'dr_tirta'").Scan(&doctorID)

	db.Exec(`
		INSERT IGNORE INTO patients (user_id, dob, gender, phone, address) 
		VALUES (?, '1995-05-15', 'Female', '081234567890', 'Malang')`, patientUserID)

	var patientID int64
	db.QueryRow("SELECT id FROM patients WHERE user_id = ?", patientUserID).Scan(&patientID)

	// Note: added the 'uploads/ct_scans/' prefix properly
	db.Exec(`
		INSERT INTO ct_scans (patient_id, uploaded_by, doctor_id, image_url, status) 
		VALUES (?, ?, ?, 'uploads/ct_scans/1775878419040821800_WhatsApp Image 2026-04-11 at 10.32.39.jpeg', 'analyzed')`, patientID, medrecID, doctorID)
	
	db.Exec(`
		INSERT INTO ct_scans (patient_id, uploaded_by, doctor_id, image_url, status) 
		VALUES (?, ?, ?, 'uploads/ct_scans/1775880808820643100_parabasal.jpeg', 'uploaded')`, patientID, medrecID, doctorID)

	var scanID int64
	db.QueryRow("SELECT id FROM ct_scans WHERE image_url = 'uploads/ct_scans/1775878419040821800_WhatsApp Image 2026-04-11 at 10.32.39.jpeg'").Scan(&scanID)

	db.Exec(`
		INSERT INTO ai_results (scan_id, prediction_label, confidence, risk_level, analyzed_image_url) 
		VALUES (?, 'Normal', 98.5, 'Low', 'uploads/ct_scans/1775878419040821800_WhatsApp Image 2026-04-11 at 10.32.39.jpeg')`, scanID)

	db.Exec(`
		INSERT INTO health_records (patient_id, created_by, heart_rate, temperature, alert_status) 
		VALUES (?, ?, 75, 36.5, 'normal')`, patientID, medrecID)

	days := []string{"Monday", "Tuesday", "Wednesday", "Thursday", "Friday"}
	for _, day := range days {
		db.Exec(`
			INSERT IGNORE INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, max_patients, is_available) 
			VALUES (?, ?, '08:00:00', '16:00:00', 15, true)`, doctorID, day)
	}

	log.Println("Database successfully seeded with requested accounts and dummy data!")
}

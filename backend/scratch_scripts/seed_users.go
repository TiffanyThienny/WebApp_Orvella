//go:build ignore

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
			log.Printf("Created user: %s (Role: %d)", u.Username, u.RoleID)
		}
	}
	// Seed a test patient
	patientHash, _ := utils.HashPassword("patient123")
	_, _ = db.Exec(`
		INSERT INTO users (role_id, username, password_hash, full_name, is_profile_complete) 
		VALUES (3, 'pasien_test', ?, 'Pasien Test Dummy', true)
		ON DUPLICATE KEY UPDATE id=id`, patientHash)
	
	var userID int64
	db.QueryRow("SELECT id FROM users WHERE username = 'pasien_test'").Scan(&userID)

	db.Exec(`
		INSERT IGNORE INTO patients (user_id, dob, gender, phone, address) 
		VALUES (?, '1990-01-01', 'Male', '08123456789', 'Jl. Test No. 123')`, userID)

	var patientID int64
	db.QueryRow("SELECT id FROM patients WHERE user_id = ?", userID).Scan(&patientID)
	log.Printf("Created Test Patient User: pasien_test (Patient ID: %d)", patientID)

	// Seed recurring schedules for Dr. Tirta
	var doctorID int64
	db.QueryRow("SELECT id FROM users WHERE username = 'dr_tirta'").Scan(&doctorID)
	if doctorID > 0 {
		days := []string{"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"}
		for _, day := range days {
			_, err := db.Exec(`
				INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, max_patients, is_available)
				VALUES (?, ?, '09:00:00', '15:00:00', 10, true)
				ON DUPLICATE KEY UPDATE 
					start_time = VALUES(start_time),
					end_time = VALUES(end_time),
					max_patients = VALUES(max_patients),
					is_available = VALUES(is_available)`,
				doctorID, day,
			)
			if err != nil {
				log.Printf("Error seeding schedule for %s: %v", day, err)
			}
		}
		log.Println("✅ Seeded recurring schedules for Dr. Tirta successfully!")
	}
}

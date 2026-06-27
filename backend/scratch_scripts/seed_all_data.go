//go:build ignore

package main

import (
	"backend/config"
	"backend/pkg/utils"
	"fmt"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	patientHash, _ := utils.HashPassword("password123")
	
	// Create 5 patients
	for i := 1; i <= 5; i++ {
		username := fmt.Sprintf("pasien%d", i)
		fullName := fmt.Sprintf("Pasien Sample %d", i)
		
		db.Exec("INSERT INTO users (role_id, username, password_hash, full_name, is_profile_complete) VALUES (3, ?, ?, ?, true) ON DUPLICATE KEY UPDATE id=id", 
			username, patientHash, fullName)
		
		var uid int
		db.QueryRow("SELECT id FROM users WHERE username=?", username).Scan(&uid)
		
		db.Exec("INSERT IGNORE INTO patients (user_id, dob, gender, phone, address) VALUES (?, '1990-01-01', 'Female', ?, 'Alamat Dummy')", 
			uid, fmt.Sprintf("0812300%d", i))
		
		var pid int
		db.QueryRow("SELECT id FROM patients WHERE user_id=?", uid).Scan(&pid)
		
		// Find Dr. Tirta ID
		var doctorID int
		db.QueryRow("SELECT id FROM users WHERE username='dr_tirta'").Scan(&doctorID)
		if doctorID == 0 {
			doctorID = 2 // fallback
		}

		// Create some health records for the chart
		for j := 1; j <= 5; j++ {
			score := 80 + (i * j % 20)
			db.Exec("INSERT INTO health_records (patient_id, created_by, systolic, diastolic, heart_rate, weight, health_score, created_at) VALUES (?, 1, 120, 80, 75, 65, ?, DATE_SUB(NOW(), INTERVAL ? DAY))", 
				pid, score, j*2)
		}

		// Create one scan for each patient assigned to Dr. Tirta
		db.Exec("INSERT INTO ct_scans (patient_id, uploaded_by, doctor_id, image_url, status) VALUES (?, 4, ?, 'uploads/mock.png', 'pending')", 
			pid, doctorID)
	}

	log.Println("✅ Data dummy berhasil ditambahkan! Dashboard sekarang sudah terisi.")
}

//go:build ignore

package main

import (
	"backend/config"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	// Add missing columns to health_records
	_, _ = db.Exec("ALTER TABLE health_records ADD COLUMN temperature FLOAT AFTER heart_rate")
	_, _ = db.Exec("ALTER TABLE health_records ADD COLUMN oxygen_level INT AFTER temperature")

	// Add columns to users if missing
	_, _ = db.Exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20)")
	_, _ = db.Exec("ALTER TABLE users ADD COLUMN address TEXT")

	// Add columns to ct_scans for assignment if missing
	_, _ = db.Exec("ALTER TABLE ct_scans ADD COLUMN doctor_id INT AFTER uploaded_by")
	_, _ = db.Exec("ALTER TABLE ct_scans ADD CONSTRAINT fk_doctor FOREIGN KEY (doctor_id) REFERENCES users(id)")

	log.Println("✅ Database schema updated with missing columns.")
}

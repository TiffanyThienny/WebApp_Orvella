//go:build ignore

package main

import (
	"backend/config"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	query := `
	CREATE TABLE IF NOT EXISTS appointments (
		id INT AUTO_INCREMENT PRIMARY KEY,
		patient_id INT NOT NULL,
		doctor_id INT NOT NULL,
		appointment_date DATETIME NOT NULL,
		notes TEXT,
		status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
		FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
	);`

	_, err := db.Exec(query)
	if err != nil {
		log.Fatal(err)
	}
	log.Println("Appointments table created successfully")
}

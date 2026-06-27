//go:build ignore

package main

import (
	"backend/config"
	"backend/internal/model"
	"fmt"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	rows, err := db.Query(`SELECT id, systolic, diastolic, heart_rate, temperature, oxygen_level, weight FROM health_records`)
	if err != nil {
		log.Fatal(err)
	}
	defer rows.Close()

	fmt.Println("Applying health scoring logic to existing records...")

	var updates []model.HealthRecord
	for rows.Next() {
		var r model.HealthRecord
		err := rows.Scan(&r.ID, &r.Systolic, &r.Diastolic, &r.HeartRate, &r.Temperature, &r.OxygenLevel, &r.Weight)
		if err != nil {
			log.Printf("Scan error: %v", err)
			continue
		}

		// Apply Scoring (Copied from RecordService)
		score := 0.0
		if r.Systolic >= 110 && r.Systolic <= 130 { score += 25 } else if r.Systolic > 140 { score += 10 } else { score += 15 }
		if r.Diastolic >= 70 && r.Diastolic <= 90 { score += 15 } else { score += 5 }
		if r.HeartRate >= 60 && r.HeartRate <= 100 { score += 20 } else { score += 5 }
		if r.OxygenLevel >= 95 { score += 25 } else if r.OxygenLevel >= 90 { score += 10 }
		if r.Temperature >= 36 && r.Temperature <= 37.5 { score += 15 } else { score += 5 }
		
		r.HealthScore = int(score)

		alertStatus := "normal"
		alertMessage := "Semua indikator kesehatan normal."
		if r.HealthScore >= 0 && r.HealthScore <= 50 {
			alertStatus = "critical"
			alertMessage = fmt.Sprintf("Kondisi berisiko: Skor kesehatan rendah (%d).", r.HealthScore)
		} else if r.HealthScore > 50 && r.HealthScore <= 75 {
			alertStatus = "warning"
			alertMessage = "Kondisi peringatan: skor menengah."
		}
		r.AlertStatus = alertStatus
		r.AlertMessage = alertMessage

		updates = append(updates, r)
	}

	for _, up := range updates {
		_, err := db.Exec(`UPDATE health_records SET health_score = ?, alert_status = ?, alert_message = ? WHERE id = ?`, 
			up.HealthScore, up.AlertStatus, up.AlertMessage, up.ID)
		if err != nil {
			log.Printf("Update error for ID %d: %v", up.ID, err)
		} else {
			fmt.Printf("Updated ID %d -> Score: %d, Status: %s\n", up.ID, up.HealthScore, up.AlertStatus)
		}
	}

	fmt.Println("All records updated successfully.")
}

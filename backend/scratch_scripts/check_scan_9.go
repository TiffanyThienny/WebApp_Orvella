//go:build ignore

package main

import (
	"database/sql"
	"log"
	"backend/config"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	var status string
	err := db.QueryRow("SELECT status FROM ct_scans WHERE id = 9").Scan(&status)
	if err != nil {
		log.Fatalf("Failed to query scan: %v", err)
	}
	log.Printf("Scan 9 status: %s", status)

	var label, analyzedURL string
	var confidence float64
	err = db.QueryRow("SELECT prediction_label, confidence, analyzed_image_url FROM ai_results WHERE scan_id = 9").
		Scan(&label, &confidence, &analyzedURL)
	if err != nil {
		if err == sql.ErrNoRows {
			log.Println("No AI result found yet for scan 9")
		} else {
			log.Fatalf("Failed to query ai_results: %v", err)
		}
	} else {
		log.Printf("AI Result found! Label: %s, Confidence: %f, URL: %s", label, confidence, analyzedURL)
	}
}

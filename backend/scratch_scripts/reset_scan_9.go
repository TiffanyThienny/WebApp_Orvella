//go:build ignore

package main

import (
	"log"
	"backend/config"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	_, err := db.Exec("DELETE FROM ai_results WHERE scan_id = 9")
	if err != nil {
		log.Fatalf("Failed to delete ai_results: %v", err)
	}
	log.Println("Deleted ai_results for scan 9")

	_, err = db.Exec("UPDATE ct_scans SET status = 'uploaded' WHERE id = 9")
	if err != nil {
		log.Fatalf("Failed to update ct_scans: %v", err)
	}
	log.Println("Updated ct_scans status to 'uploaded' for scan 9")
}

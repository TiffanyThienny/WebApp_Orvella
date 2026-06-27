//go:build ignore

package main

import (
	"backend/config"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	_, err := db.Exec(`
		ALTER TABLE health_records
		ADD COLUMN temperature FLOAT DEFAULT NULL AFTER heart_rate,
		ADD COLUMN oxygen_level INT DEFAULT NULL AFTER temperature;
	`)
	if err != nil {
		log.Printf("Warning/Error: %v", err)
	} else {
		log.Println("Added temperature and oxygen_level to health_records")
	}

	// Make sure schema.sql matches this!
}

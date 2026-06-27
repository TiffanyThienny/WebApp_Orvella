//go:build ignore

package main

import (
	"backend/config"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	_, err := db.Exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT '';")
	if err != nil {
		log.Println("Error alerting users table or it already has email:", err)
	} else {
		log.Println("Successfully added 'email' column to users table.")
	}
}

//go:build ignore

package main

import (
	"log"
	"backend/config"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	rows, err := db.Query("SELECT u.username, r.name FROM users u JOIN roles r ON u.role_id = r.id")
	if err != nil {
		log.Fatal(err)
	}
	defer rows.Close()

	log.Println("--- User Role Audit ---")
	for rows.Next() {
		var username, role string
		rows.Scan(&username, &role)
		log.Printf("User: %s | Role: %s\n", username, role)
	}
}

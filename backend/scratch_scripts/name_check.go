//go:build ignore

package main

import (
	"log"
	"backend/config"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	var name string
	err := db.QueryRow("SELECT full_name FROM users WHERE username='dr_tirta'").Scan(&name)
	if err != nil {
		log.Printf("ERROR: %v", err)
	}
	log.Printf("FULL NAME: |%s|", name)
}

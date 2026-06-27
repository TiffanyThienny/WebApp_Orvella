//go:build ignore

package main

import (
	"backend/config"
	"fmt"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	rows, err := db.Query("SELECT id, username, password_hash FROM users")
	if err != nil {
		log.Fatal(err)
	}
	defer rows.Close()

	for rows.Next() {
		var id int
		var username, password string
		rows.Scan(&id, &username, &password)
		fmt.Printf("ID: %d | User: %s | Hash: %s\n", id, username, password)
	}
}

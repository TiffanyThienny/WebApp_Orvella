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

	rows, err := db.Query("SELECT id, user_id FROM patients")
	if err != nil {
		log.Fatal(err)
	}
	defer rows.Close()

	fmt.Println("Available Patients:")
	for rows.Next() {
		var id, uid int
		rows.Scan(&id, &uid)
		fmt.Printf("ID: %d | UserID: %d\n", id, uid)
	}
}

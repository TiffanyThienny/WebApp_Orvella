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

	rows, err := db.Query("SELECT id, username, role_id, is_profile_complete, full_name FROM users")
	if err != nil {
		log.Fatal(err)
	}
	defer rows.Close()

	for rows.Next() {
		var id, roleID int
		var username, fullName string
		var isComplete bool
		rows.Scan(&id, &username, &roleID, &isComplete, &fullName)
		fmt.Printf("ID: %d, Username: %s, RoleID: %d, isComplete: %v, FullName: %s\n", id, username, roleID, isComplete, fullName)
	}
}

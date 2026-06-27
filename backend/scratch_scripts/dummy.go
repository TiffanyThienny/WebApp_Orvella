//go:build ignore

package main

import (
	"database/sql"
	"fmt"
	_ "github.com/go-sql-driver/mysql"
)

func main() {
	db, err := sql.Open("mysql", "root:@tcp(localhost:3306)/fp")
	if err != nil {
		fmt.Println("Error connecting to db")
		return
	}
	defer db.Close()

	rows, err := db.Query("SELECT username, role_id FROM users")
	if err != nil {
		fmt.Println("Error fetching users:", err)
		return
	}
	defer rows.Close()

	fmt.Println("Users:")
	for rows.Next() {
		var u string
		var r int
		rows.Scan(&u, &r)
		fmt.Printf("User: %s, Role: %d\n", u, r)
	}
}

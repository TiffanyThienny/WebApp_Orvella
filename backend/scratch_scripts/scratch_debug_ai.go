//go:build ignore

package main

import (
	"database/sql"
	"fmt"
	"log"

	_ "github.com/go-sql-driver/mysql"
)

func main() {
	db, err := sql.Open("mysql", "root:@tcp(127.0.0.1:3306)/fp")
	if err != nil {
		log.Fatal(err)
	}
	defer db.Close()

	fmt.Println("--- CT SCANS ---")
	rows, _ := db.Query("SELECT id, status FROM ct_scans")
	for rows.Next() {
		var id int
		var status string
		rows.Scan(&id, &status)
		fmt.Printf("ID: %d | Status: %s\n", id, status)
	}

	fmt.Println("\n--- AI RESULTS ---")
	rows, _ = db.Query("SELECT scan_id, prediction_label, result FROM ai_results")
	for rows.Next() {
		var id int
		var label, result string
		rows.Scan(&id, &label, &result)
		fmt.Printf("ScanID: %d | Label: %s | Result: %s\n", id, label, result)
	}
}

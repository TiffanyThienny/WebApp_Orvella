//go:build ignore

package main

import (
	"log"
	"backend/config"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	tables := []string{"users", "roles", "patients", "ct_scans", "health_records", "appointments"}
	for _, table := range tables {
		log.Printf("--- Table: %s ---\n", table)
		rows, err := db.Query("DESCRIBE " + table)
		if err != nil {
			log.Printf("ERROR on %s: %v\n", table, err)
			continue
		}
		for rows.Next() {
			var f, t, n, k, d, e string
			rows.Scan(&f, &t, &n, &k, &d, &e)
			log.Printf("Field: %s\n", f)
		}
		rows.Close()
	}
}

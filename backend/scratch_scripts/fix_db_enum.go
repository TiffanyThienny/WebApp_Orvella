//go:build ignore

package main

import (
	"database/sql"
	"fmt"
	"log"
	"os"

	_ "github.com/go-sql-driver/mysql"
	"github.com/joho/godotenv"
)

func main() {
	godotenv.Load()

	dbHost := os.Getenv("DB_HOST")
	if dbHost == "" {
		dbHost = "localhost"
	}
	dbPort := os.Getenv("DB_PORT")
	if dbPort == "" {
		dbPort = "3306"
	}
	dbUser := os.Getenv("DB_USER")
	if dbUser == "" {
		dbUser = "root"
	}
	dbPass := os.Getenv("DB_PASSWORD")
	dbName := os.Getenv("DB_NAME")
	if dbName == "" {
		dbName = "fp"
	}

	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?parseTime=true", dbUser, dbPass, dbHost, dbPort, dbName)
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		log.Fatal("Error connecting to database:", err)
	}
	defer db.Close()

	// Alter tables to use VARCHAR(50) instead of restrictive ENUMs
	queries := []string{
		"ALTER TABLE ct_scans MODIFY COLUMN status VARCHAR(50) DEFAULT 'uploaded'",
		"ALTER TABLE health_records MODIFY COLUMN alert_status VARCHAR(50) DEFAULT 'normal'",
		"ALTER TABLE appointments MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'",
		"ALTER TABLE diagnoses MODIFY COLUMN status VARCHAR(50) DEFAULT 'draft'",
	}

	for _, q := range queries {
		fmt.Printf("Executing: %s\n", q)
		_, err := db.Exec(q)
		if err != nil {
			log.Printf("Error executing query: %v", err)
		} else {
			fmt.Println("Success!")
		}
	}

	fmt.Println("DB ENUM limits fixed successfully!")
}

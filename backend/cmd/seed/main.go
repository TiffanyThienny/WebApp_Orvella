package main

import (
	"database/sql"
	"fmt"
	"log"
	"os"
	"strings"

	"backend/pkg/utils"

	_ "github.com/go-sql-driver/mysql"
	"github.com/joho/godotenv"
)

func main() {
	godotenv.Load()

	// Connect to MySQL server first (no DB specified)
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

	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/", dbUser, dbPass, dbHost, dbPort)
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		log.Fatal("Error connecting to mysql root:", err)
	}
	defer db.Close()

	if err := db.Ping(); err != nil {
		log.Fatal("Cannot ping mysql:", err)
	}

	// Create Database
	fmt.Println("Creating Database...")
	_, err = db.Exec(fmt.Sprintf("CREATE DATABASE IF NOT EXISTS %s", dbName))
	if err != nil {
		log.Fatal("Error creating DB:", err)
	}

	// Connect to the specific DB
	dsnDB := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?parseTime=true&multiStatements=true", dbUser, dbPass, dbHost, dbPort, dbName)
	dbConn, err := sql.Open("mysql", dsnDB)
	if err != nil {
		log.Fatal("Error connecting to created DB:", err)
	}
	defer dbConn.Close()

	// Read Schema
	schemaBytes, err := os.ReadFile("db/schema.sql")
	if err != nil {
		log.Fatal("Failed reading schema.sql:", err)
	}

	fmt.Println("Applying schema...")
	schemaString := string(schemaBytes)
	statements := strings.Split(schemaString, ";")
	for _, stmt := range statements {
		stmt = strings.TrimSpace(stmt)
		if stmt == "" {
			continue
		}
		_, err = dbConn.Exec(stmt)
		if err != nil {
			log.Printf("Warning executing stmt '%s': %v\n", stmt, err)
		}
	}

	// Seed Users
	fmt.Println("Seeding Users...")

	hashPass, err := utils.HashPassword("password123")
	if err != nil {
		log.Fatal("Error hashing password:", err)
	}

	type UserSeed struct {
		RoleID   int
		Username string
		FullName string
		Email    string
	}

	var users = []UserSeed{
		{RoleID: 1, Username: "admin", FullName: "System Admin", Email: "admin@orvella.ai"},
		{RoleID: 2, Username: "doctor1", FullName: "Dr. Smith", Email: "doctor.smith@orvella.ai"},
		{RoleID: 3, Username: "patient1", FullName: "John Doe", Email: "john.doe@gmail.com"},
	}

	for _, u := range users {
		var exists int
		err := dbConn.QueryRow("SELECT COUNT(*) FROM users WHERE username = ?", u.Username).Scan(&exists)
		if err != nil {
			log.Println("Error checking user:", err)
			continue
		}

		if exists == 0 {
			res, err := dbConn.Exec(
				"INSERT INTO users (role_id, username, email, password_hash, full_name) VALUES (?, ?, ?, ?, ?)",
				u.RoleID, u.Username, u.Email, hashPass, u.FullName,
			)
			if err != nil {
				log.Println("Error inserting user:", err)
				continue
			}

			if u.RoleID == 3 {
				userID, err := res.LastInsertId()
				if err != nil {
					log.Println("Error getting user ID:", err)
					continue
				}

				_, err = dbConn.Exec(
					"INSERT INTO patients (user_id, dob, gender, phone) VALUES (?, '1990-01-01', 'Male', '08123456789')",
					userID,
				)
				if err != nil {
					log.Println("Error inserting patient:", err)
				}
			}
		}
	}

	fmt.Println("Database successfully initialized and seeded!")
	fmt.Println("Default Password for all users: password123")
}

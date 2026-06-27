package main

import (
	"database/sql"
	"fmt"
	_ "github.com/go-sql-driver/mysql"
	"golang.org/x/crypto/bcrypt"
)

func main() {
	db, err := sql.Open("mysql", "root:@tcp(127.0.0.1:3306)/fp4")
	if err != nil {
		fmt.Println("DB Open error:", err)
		return
	}
	defer db.Close()

	hashed, err := bcrypt.GenerateFromPassword([]byte("password"), bcrypt.DefaultCost)
	if err != nil {
		fmt.Println("Bcrypt error:", err)
		return
	}

	_, err = db.Exec("UPDATE users SET password_hash = ? WHERE username = 'doctor'", string(hashed))
	if err != nil {
		fmt.Println("UPDATE error:", err)
		return
	}

	fmt.Println("Successfully updated doctor password to: password with hash:", string(hashed))
}

//go:build ignore

package main

import (
	"backend/config"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	_, err := db.Exec("UPDATE users SET email = 'admin@orvella.ai' WHERE username = 'admin'")
	if err != nil { log.Println(err) }
	_, err = db.Exec("UPDATE users SET email = 'doctor.smith@orvella.ai' WHERE username = 'doctor1'")
	if err != nil { log.Println(err) }
	_, err = db.Exec("UPDATE users SET email = 'john.doe@gmail.com' WHERE username = 'patient1'")
	if err != nil { log.Println(err) }

	log.Println("Successfully updated mock emails for existing users.")
}

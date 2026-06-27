//go:build ignore

package main

import (
	"backend/config"
	"log"
)

func main() {
	// Menghubungkan ke DB
	db := config.InitDB()
	defer db.Close()

	log.Println("🛠️ Menjalankan Sinkronisasi Skema Database Admin...")

	// 1. Tambahkan kolom created_at ke users jika belum ada
	// Kita gunakan pendekatan ADD COLUMN jika tidak ada
	_, _ = db.Exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP")
	
	// 2. Tambahkan kolom token jika belum ada
	_, _ = db.Exec("ALTER TABLE users ADD COLUMN token TEXT")

    // 3. Pastikan kolom di ct_scans juga lengkap untuk statistik
    _, _ = db.Exec("ALTER TABLE ct_scans ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP")

	log.Println("✅ Sinkronisasi Selesai. Silakan REFRESH Dashboard Admin.")
}

package main

import (
	"backend/config"
	"log"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	// 1. Create table
	query := `
	CREATE TABLE IF NOT EXISTS site_configs (
		id INT AUTO_INCREMENT PRIMARY KEY,
		config_key VARCHAR(50) NOT NULL UNIQUE,
		config_value TEXT NOT NULL,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	);`
	
	_, err := db.Exec(query)
	if err != nil {
		log.Fatalf("Table creation failed: %v", err)
	}
	log.Println("SiteConfigs table created successfully")

	// 2. Add Specialty to users
	_, _ = db.Exec("ALTER TABLE users ADD COLUMN specialty VARCHAR(100)")
	log.Println("Specialty column added (if not exists)")

	// 3. Seed initial data
	initialConfigs := map[string]string{
		"hero_title": "Masa Depan Kesehatan didukung oleh AI.",
		"hero_subtitle": "Platform modern yang menggabungkan kecerdasan buatan dan validasi medis profesional untuk deteksi CT Scan dan pemantauan kondisi pasien.",
		"hero_image": "/images/hero.png",
		"stats_patients": "10k+",
		"stats_accuracy": "99.4%",
		"stats_doctors": "150+",
		"stats_scans": "45k+",
		"contact_address": "Cyberpark Tower, Lt 12\nJakarta Selatan, 12950",
		"contact_phone": "+62 821-2345-6789",
		"contact_email": "hello@orvella.ai",
	}

	for k, v := range initialConfigs {
		_, err = db.Exec("INSERT IGNORE INTO site_configs (config_key, config_value) VALUES (?, ?)", k, v)
		if err != nil {
			log.Printf("Failed to seed %s: %v", k, err)
		}
	}
	log.Println("Initial site configurations seeded")
}

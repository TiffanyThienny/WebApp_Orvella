package repository

import (
	"database/sql"
	"backend/internal/model"
)

type ConfigRepository struct {
	DB *sql.DB
}

func NewConfigRepository(db *sql.DB) *ConfigRepository {
	return &ConfigRepository{DB: db}
}

func (r *ConfigRepository) GetConfigs() ([]model.SiteConfig, error) {
	rows, err := r.DB.Query("SELECT id, config_key, config_value, updated_at FROM site_configs")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var configs []model.SiteConfig
	for rows.Next() {
		var c model.SiteConfig
		if err := rows.Scan(&c.ID, &c.ConfigKey, &c.ConfigValue, &c.UpdatedAt); err != nil {
			return nil, err
		}
		configs = append(configs, c)
	}
	return configs, nil
}

func (r *ConfigRepository) UpdateConfig(key, value string) error {
	_, err := r.DB.Exec("INSERT INTO site_configs (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?", key, value, value)
	return err
}

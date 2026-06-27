package repository

import (
	"database/sql"
	"backend/internal/model"
)

type RecordRepository struct {
	DB *sql.DB
}

func NewRecordRepository(db *sql.DB) *RecordRepository {
	return &RecordRepository{DB: db}
}

func (r *RecordRepository) Create(rec *model.HealthRecord) error {
	_, err := r.DB.Exec(`INSERT INTO health_records 
		(patient_id, scan_id, created_by, systolic, diastolic, heart_rate, temperature, oxygen_level, weight, health_score, alert_status, alert_message, notes) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		rec.PatientID, rec.ScanID, rec.CreatedBy, rec.Systolic, rec.Diastolic, rec.HeartRate, rec.Temperature, rec.OxygenLevel, rec.Weight, rec.HealthScore, rec.AlertStatus, rec.AlertMessage, rec.Notes)
	return err
}

func (r *RecordRepository) GetByPatientID(patientID int) ([]model.HealthRecord, error) {
	rows, err := r.DB.Query(`SELECT id, patient_id, scan_id, created_by, systolic, diastolic, heart_rate, temperature, oxygen_level, weight, health_score, alert_status, alert_message, notes, created_at 
		FROM health_records WHERE patient_id = ? ORDER BY created_at DESC`, patientID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var records []model.HealthRecord
	for rows.Next() {
		var rec model.HealthRecord
		err := rows.Scan(&rec.ID, &rec.PatientID, &rec.ScanID, &rec.CreatedBy, &rec.Systolic, &rec.Diastolic, &rec.HeartRate, &rec.Temperature, &rec.OxygenLevel, &rec.Weight, &rec.HealthScore, &rec.AlertStatus, &rec.AlertMessage, &rec.Notes, &rec.CreatedAt)
		if err != nil {
			return nil, err
		}
		records = append(records, rec)
	}
	return records, nil
}

func (r *RecordRepository) Update(rec *model.HealthRecord) error {
	_, err := r.DB.Exec(`UPDATE health_records SET 
		systolic = ?, diastolic = ?, heart_rate = ?, temperature = ?, 
		oxygen_level = ?, weight = ?, health_score = ?, alert_status = ?, 
		alert_message = ?, notes = ? 
		WHERE id = ?`,
		rec.Systolic, rec.Diastolic, rec.HeartRate, rec.Temperature,
		rec.OxygenLevel, rec.Weight, rec.HealthScore, rec.AlertStatus,
		rec.AlertMessage, rec.Notes, rec.ID)
	return err
}

func (r *RecordRepository) Delete(id int) error {
	_, err := r.DB.Exec("DELETE FROM health_records WHERE id = ?", id)
	return err
}

func (r *RecordRepository) GetByID(id int) (*model.HealthRecord, error) {
	row := r.DB.QueryRow(`SELECT id, patient_id, scan_id, created_by, 
		systolic, diastolic, heart_rate, temperature, oxygen_level, weight, 
		health_score, alert_status, alert_message, notes, created_at 
		FROM health_records WHERE id = ?`, id)
	
	var rec model.HealthRecord
	err := row.Scan(&rec.ID, &rec.PatientID, &rec.ScanID, &rec.CreatedBy, &rec.Systolic, &rec.Diastolic, &rec.HeartRate, &rec.Temperature, &rec.OxygenLevel, &rec.Weight, &rec.HealthScore, &rec.AlertStatus, &rec.AlertMessage, &rec.Notes, &rec.CreatedAt)
	if err != nil {
		return nil, err
	}
	return &rec, nil
}

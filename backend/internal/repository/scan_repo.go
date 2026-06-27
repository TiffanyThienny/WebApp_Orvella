package repository

import (
	"database/sql"
	"errors"

	"backend/internal/model"
)

type ScanRepository struct {
	DB *sql.DB
}

func NewScanRepository(db *sql.DB) *ScanRepository {
	return &ScanRepository{DB: db}
}

func (r *ScanRepository) CreateScan(scan *model.CTScan) error {
	res, err := r.DB.Exec("INSERT INTO ct_scans (patient_id, uploaded_by, image_url, status) VALUES (?, ?, ?, ?)",
		scan.PatientID, scan.UploadedBy, scan.ImageURL, scan.Status)
	if err != nil {
		return err
	}
	id, err := res.LastInsertId()
	if err != nil {
		return err
	}
	scan.ID = int(id)
	return nil
}

func (r *ScanRepository) UpdateStatus(scanID int, status string) error {
	_, err := r.DB.Exec("UPDATE ct_scans SET status = ? WHERE id = ?", status, scanID)
	return err
}

func (r *ScanRepository) SaveAIResult(res *model.AIResult) error {
	// Delete any existing AI result for this scan so re-analysis works
	_, _ = r.DB.Exec("DELETE FROM ai_results WHERE scan_id = ?", res.ScanID)

	result, err := r.DB.Exec("INSERT INTO ai_results (scan_id, prediction_label, result_text, confidence, risk_level, analyzed_image_url) VALUES (?, ?, ?, ?, ?, ?)",
		res.ScanID, res.PredictionLabel, res.ResultText, res.Confidence, res.RiskLevel, res.AnalyzedImageURL)
	if err != nil {
		return err
	}
	id, _ := result.LastInsertId()
	res.ID = int(id)
	return nil
}

// AssignDoctor assigns a doctor without changing status
func (r *ScanRepository) AssignDoctor(scanID int, doctorID int) error {
	_, err := r.DB.Exec("UPDATE ct_scans SET doctor_id = ? WHERE id = ?", doctorID, scanID)
	return err
}

func (r *ScanRepository) CreateDiagnosis(diag *model.Diagnosis) error {
	res, err := r.DB.Exec("INSERT INTO diagnoses (scan_id, doctor_id, notes, status) VALUES (?, ?, ?, ?)",
		diag.ScanID, diag.DoctorID, diag.Notes, diag.Status)
	if err != nil {
		return err
	}
	id, _ := res.LastInsertId()
	diag.ID = int(id)
	return nil
}

func (r *ScanRepository) UpdateDiagnosisStatus(diagID int, status string) error {
	_, err := r.DB.Exec("UPDATE diagnoses SET status = ? WHERE id = ?", status, diagID)
	return err
}

func (r *ScanRepository) GetDiagnosisByID(diagID int) (*model.Diagnosis, error) {
	var d model.Diagnosis
	err := r.DB.QueryRow("SELECT id, scan_id, doctor_id, notes, status FROM diagnoses WHERE id = ?", diagID).
		Scan(&d.ID, &d.ScanID, &d.DoctorID, &d.Notes, &d.Status)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, errors.New("diagnosis not found")
		}
		return nil, err
	}
	return &d, nil
}

// populateScanList runs a base query and joins patient name, doctor name, AI results, and diagnoses
func (r *ScanRepository) populateScanList(query string, args ...interface{}) ([]model.CTScan, error) {
	fullQuery := `
		SELECT 
			s.id, s.patient_id,
			IFNULL(pu.full_name, CONCAT('Patient #', s.patient_id)) as patient_name,
			s.uploaded_by, s.doctor_id,
			IFNULL(du.full_name, '') as doctor_name,
			s.image_url, s.status, s.created_at,
			ar.id, ar.scan_id, ar.prediction_label, ar.result_text, ar.confidence, ar.risk_level, ar.analyzed_image_url,
			d.id, d.scan_id, d.doctor_id, d.notes, d.status
		FROM (` + query + `) s
		LEFT JOIN patients pt ON s.patient_id = pt.id
		LEFT JOIN users pu ON pt.user_id = pu.id
		LEFT JOIN users du ON s.doctor_id = du.id
		LEFT JOIN ai_results ar ON s.id = ar.scan_id
		LEFT JOIN diagnoses d ON s.id = d.scan_id
		ORDER BY s.created_at DESC
	`

	rows, err := r.DB.Query(fullQuery, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var scans []model.CTScan
	for rows.Next() {
		var s model.CTScan
		var dID sql.NullInt64
		var patientName string
		var doctorName sql.NullString

		var aiID, aiScanID sql.NullInt64
		var aiLabel, aiResultText, aiRisk, aiAnalyzedURL sql.NullString
		var aiConf sql.NullFloat64

		var diagID, diagScanID, diagDocID sql.NullInt64
		var diagNotes, diagStatus sql.NullString

		if err := rows.Scan(
			&s.ID, &s.PatientID, &patientName,
			&s.UploadedBy, &dID, &doctorName,
			&s.ImageURL, &s.Status, &s.CreatedAt,
			&aiID, &aiScanID, &aiLabel, &aiResultText, &aiConf, &aiRisk, &aiAnalyzedURL,
			&diagID, &diagScanID, &diagDocID, &diagNotes, &diagStatus,
		); err != nil {
			return nil, err
		}

		if dID.Valid {
			id := int(dID.Int64)
			s.DoctorID = &id
		}

		// Attach patient info
		s.Patient = &model.Patient{
			ID: s.PatientID,
			User: &model.User{
				FullName: patientName,
			},
		}

		// Attach doctor info if assigned
		if doctorName.Valid && doctorName.String != "" {
			if s.DoctorID != nil {
				s.Doctor = &model.User{
					ID:       *s.DoctorID,
					FullName: doctorName.String,
				}
			}
		}

		if aiID.Valid {
			s.AIResult = &model.AIResult{
				ID:               int(aiID.Int64),
				ScanID:           int(aiScanID.Int64),
				PredictionLabel:  aiLabel.String,
				ResultText:       aiResultText.String,
				Confidence:       aiConf.Float64,
				RiskLevel:        aiRisk.String,
				AnalyzedImageURL: aiAnalyzedURL.String,
			}
		}

		if diagID.Valid {
			s.Diagnosis = &model.Diagnosis{
				ID:       int(diagID.Int64),
				ScanID:   int(diagScanID.Int64),
				DoctorID: int(diagDocID.Int64),
				Notes:    diagNotes.String,
				Status:   diagStatus.String,
			}
		}

		scans = append(scans, s)
	}
	return scans, nil
}

func (r *ScanRepository) GetScansByPatient(patientID int) ([]model.CTScan, error) {
	return r.populateScanList("SELECT id, patient_id, uploaded_by, doctor_id, image_url, status, created_at FROM ct_scans WHERE patient_id = ? ORDER BY created_at DESC", patientID)
}

func (r *ScanRepository) GetScansByDoctor(doctorID int) ([]model.CTScan, error) {
	return r.populateScanList("SELECT id, patient_id, uploaded_by, doctor_id, image_url, status, created_at FROM ct_scans WHERE doctor_id = ? ORDER BY created_at DESC", doctorID)
}

func (r *ScanRepository) GetAllScans() ([]model.CTScan, error) {
	return r.populateScanList("SELECT id, patient_id, uploaded_by, doctor_id, image_url, status, created_at FROM ct_scans ORDER BY created_at DESC")
}

func (r *ScanRepository) GetScanByID(scanID int) (*model.CTScan, error) {
	var s model.CTScan
	var dID sql.NullInt64
	err := r.DB.QueryRow("SELECT id, patient_id, uploaded_by, doctor_id, image_url, status, created_at FROM ct_scans WHERE id = ?", scanID).
		Scan(&s.ID, &s.PatientID, &s.UploadedBy, &dID, &s.ImageURL, &s.Status, &s.CreatedAt)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, errors.New("scan not found")
		}
		return nil, err
	}
	if dID.Valid {
		id := int(dID.Int64)
		s.DoctorID = &id
	}
	return &s, nil
}

func (r *ScanRepository) GetApprovedScansByPatient(patientID int) ([]model.CTScan, error) {
	return r.populateScanList("SELECT id, patient_id, uploaded_by, doctor_id, image_url, status, created_at FROM ct_scans WHERE patient_id = ? AND status = 'approved'", patientID)
}

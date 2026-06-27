package repository

import (
	"database/sql"
	"backend/internal/model"
)

type AppointmentRepository struct {
	DB *sql.DB
}

func NewAppointmentRepository(db *sql.DB) *AppointmentRepository {
	return &AppointmentRepository{DB: db}
}

func (r *AppointmentRepository) Create(a *model.Appointment) error {
	res, err := r.DB.Exec("INSERT INTO appointments (patient_id, doctor_id, appointment_date, notes) VALUES (?, ?, ?, ?)",
		a.PatientID, a.DoctorID, a.AppointmentDate, a.Notes)
	if err != nil {
		return err
	}
	id, err := res.LastInsertId()
	if err != nil {
		return err
	}
	a.ID = int(id)
	return nil
}

func (r *AppointmentRepository) GetByPatientID(patientID int) ([]model.Appointment, error) {
	rows, err := r.DB.Query(`
		SELECT a.id, a.patient_id, a.doctor_id, u.full_name as doctor_name, a.appointment_date, IFNULL(a.notes, ''), a.status, a.created_at
		FROM appointments a
		JOIN users u ON a.doctor_id = u.id
		WHERE a.patient_id = ?
		ORDER BY a.appointment_date DESC
	`, patientID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var appointments []model.Appointment
	for rows.Next() {
		var a model.Appointment
		err := rows.Scan(&a.ID, &a.PatientID, &a.DoctorID, &a.DoctorName, &a.AppointmentDate, &a.Notes, &a.Status, &a.CreatedAt)
		if err != nil {
			return nil, err
		}
		appointments = append(appointments, a)
	}
	return appointments, nil
}

func (r *AppointmentRepository) GetByDoctorID(doctorID int) ([]model.Appointment, error) {
	rows, err := r.DB.Query(`
		SELECT a.id, a.patient_id, p.full_name as patient_name, a.doctor_id, a.appointment_date, IFNULL(a.notes, ''), a.status, a.created_at
		FROM appointments a
		JOIN patients pt ON a.patient_id = pt.id
		JOIN users p ON pt.user_id = p.id
		WHERE a.doctor_id = ?
		ORDER BY a.appointment_date ASC
	`, doctorID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var appointments []model.Appointment
	for rows.Next() {
		var a model.Appointment
		err := rows.Scan(&a.ID, &a.PatientID, &a.PatientName, &a.DoctorID, &a.AppointmentDate, &a.Notes, &a.Status, &a.CreatedAt)
		if err != nil {
			return nil, err
		}
		appointments = append(appointments, a)
	}
	return appointments, nil
}

func (r *AppointmentRepository) GetByID(id int) (*model.Appointment, error) {
	var a model.Appointment
	err := r.DB.QueryRow(`
		SELECT a.id, a.patient_id, a.doctor_id, a.appointment_date, IFNULL(a.notes, ''), a.status, a.created_at
		FROM appointments a
		WHERE a.id = ?
	`, id).Scan(&a.ID, &a.PatientID, &a.DoctorID, &a.AppointmentDate, &a.Notes, &a.Status, &a.CreatedAt)
	if err != nil {
		return nil, err
	}
	return &a, nil
}

func (r *AppointmentRepository) UpdateStatus(id int, status string) error {
	_, err := r.DB.Exec("UPDATE appointments SET status = ? WHERE id = ?", status, id)
	return err
}

func (r *AppointmentRepository) GetDailyCount(doctorID int, dateStr string) (int, error) {
	var count int
	// Count appointments for the specific doctor on the specific date (ignoring time)
	err := r.DB.QueryRow(`
		SELECT COUNT(id) FROM appointments 
		WHERE doctor_id = ? AND DATE(appointment_date) = DATE(?)
		AND status != 'rejected'
	`, doctorID, dateStr).Scan(&count)
	return count, err
}

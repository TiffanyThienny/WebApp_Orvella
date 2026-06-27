package repository

import (
	"database/sql"
	"backend/internal/model"
)

type ScheduleRepository struct {
	DB *sql.DB
}

func NewScheduleRepository(db *sql.DB) *ScheduleRepository {
	return &ScheduleRepository{DB: db}
}

func (r *ScheduleRepository) CreateOrUpdate(s *model.Schedule) error {
	query := `
		INSERT INTO doctor_schedules (doctor_id, day_of_week, appointment_date, end_date, start_time, end_time, max_patients, is_available)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?)
		ON DUPLICATE KEY UPDATE 
			end_date = VALUES(end_date),
			start_time = VALUES(start_time),
			end_time = VALUES(end_time),
			max_patients = VALUES(max_patients),
			is_available = VALUES(is_available)
	`
	_, err := r.DB.Exec(query, s.DoctorID, s.DayOfWeek, s.AppointmentDate, s.EndDate, s.StartTime, s.EndTime, s.MaxPatients, s.IsAvailable)
	return err
}

func (r *ScheduleRepository) Update(id int, s *model.Schedule) error {
	query := `
		UPDATE doctor_schedules
		SET doctor_id = ?, day_of_week = ?, appointment_date = ?, end_date = ?, start_time = ?, end_time = ?, max_patients = ?, is_available = ?
		WHERE id = ?
	`
	_, err := r.DB.Exec(query, s.DoctorID, s.DayOfWeek, s.AppointmentDate, s.EndDate, s.StartTime, s.EndTime, s.MaxPatients, s.IsAvailable, id)
	return err
}

func (r *ScheduleRepository) GetByDoctorID(doctorID int) ([]model.Schedule, error) {
	query := `
		SELECT s.id, s.doctor_id, u.full_name, s.day_of_week, s.appointment_date, s.end_date, s.start_time, s.end_time, s.max_patients, s.is_available, s.created_at,
		       COALESCE((
		           SELECT COUNT(*) 
		           FROM appointments a 
		           WHERE a.doctor_id = s.doctor_id 
		             AND a.status NOT IN ('rejected', 'cancelled')
		             AND (
		                 (s.appointment_date IS NOT NULL AND DATE(a.appointment_date) = DATE(s.appointment_date))
		                 OR
		                 (s.appointment_date IS NULL AND DAYNAME(a.appointment_date) = s.day_of_week)
		             )
		       ), 0) AS booked_count
		FROM doctor_schedules s
		JOIN users u ON s.doctor_id = u.id
		WHERE s.doctor_id = ?
	`
	rows, err := r.DB.Query(query, doctorID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var schedules []model.Schedule
	for rows.Next() {
		var s model.Schedule
		if err := rows.Scan(&s.ID, &s.DoctorID, &s.DoctorName, &s.DayOfWeek, &s.AppointmentDate, &s.EndDate, &s.StartTime, &s.EndTime, &s.MaxPatients, &s.IsAvailable, &s.CreatedAt, &s.BookedCount); err != nil {
			return nil, err
		}
		schedules = append(schedules, s)
	}
	return schedules, nil
}

func (r *ScheduleRepository) GetAll() ([]model.Schedule, error) {
	query := `
		SELECT s.id, s.doctor_id, u.full_name, s.day_of_week, s.appointment_date, s.end_date, s.start_time, s.end_time, s.max_patients, s.is_available, s.created_at,
		       COALESCE((
		           SELECT COUNT(*) 
		           FROM appointments a 
		           WHERE a.doctor_id = s.doctor_id 
		             AND a.status NOT IN ('rejected', 'cancelled')
		             AND (
		                 (s.appointment_date IS NOT NULL AND DATE(a.appointment_date) = DATE(s.appointment_date))
		                 OR
		                 (s.appointment_date IS NULL AND DAYNAME(a.appointment_date) = s.day_of_week)
		             )
		       ), 0) AS booked_count
		FROM doctor_schedules s
		JOIN users u ON s.doctor_id = u.id
	`
	rows, err := r.DB.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var schedules []model.Schedule
	for rows.Next() {
		var s model.Schedule
		if err := rows.Scan(&s.ID, &s.DoctorID, &s.DoctorName, &s.DayOfWeek, &s.AppointmentDate, &s.EndDate, &s.StartTime, &s.EndTime, &s.MaxPatients, &s.IsAvailable, &s.CreatedAt, &s.BookedCount); err != nil {
			return nil, err
		}
		schedules = append(schedules, s)
	}
	return schedules, nil
}

func (r *ScheduleRepository) Delete(id int) error {
	_, err := r.DB.Exec("DELETE FROM doctor_schedules WHERE id = ?", id)
	return err
}

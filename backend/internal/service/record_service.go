package service

import (
	"errors"
	"fmt"

	"backend/internal/model"
	"backend/internal/repository"
)

type RecordService struct {
	RecordRepo *repository.RecordRepository
	UserRepo   *repository.UserRepository
}

func NewRecordService(rRepo *repository.RecordRepository, uRepo *repository.UserRepository) *RecordService {
	return &RecordService{RecordRepo: rRepo, UserRepo: uRepo}
}

func (s *RecordService) CreateRecord(rec *model.HealthRecord) error {
	// Auto-calculate Health Score
	s.applyScoring(rec)
	return s.RecordRepo.Create(rec)
}

func (s *RecordService) UpdateRecord(rec *model.HealthRecord) error {
	s.applyScoring(rec)
	return s.RecordRepo.Update(rec)
}

func (s *RecordService) applyScoring(rec *model.HealthRecord) {
	score := 0.0
	// BP (120/80 is ideal)
	if rec.Systolic >= 110 && rec.Systolic <= 130 { score += 25 } else if rec.Systolic > 140 { score += 10 } else { score += 15 }
	if rec.Diastolic >= 70 && rec.Diastolic <= 90 { score += 15 } else { score += 5 }
	// Heart Rate (60-100 is ideal)
	if rec.HeartRate >= 60 && rec.HeartRate <= 100 { score += 20 } else { score += 5 }
	// Oxygen (95-100 is ideal)
	if rec.OxygenLevel >= 95 { score += 25 } else if rec.OxygenLevel >= 90 { score += 10 }
	// Temp (36-37.5 is ideal)
	if rec.Temperature >= 36 && rec.Temperature <= 37.5 { score += 15 } else { score += 5 }
	rec.HealthScore = int(score)

	alertStatus := "normal"
	alertMessage := "Semua indikator kesehatan normal."
	if rec.HealthScore >= 0 && rec.HealthScore <= 50 {
		alertStatus = "critical"
		alertMessage = "Kondisi berisiko: Skor kesehatan rendah (" + fmt.Sprint(rec.HealthScore) + ")."
	} else if rec.HealthScore > 50 && rec.HealthScore <= 75 {
		alertStatus = "warning"
		alertMessage = "Kondisi peringatan: skor menengah."
	}
	rec.AlertStatus = alertStatus
	rec.AlertMessage = alertMessage
}

func (s *RecordService) DeleteRecord(id int) error {
	return s.RecordRepo.Delete(id)
}

func (s *RecordService) GetRecordByID(id int) (*model.HealthRecord, error) {
	return s.RecordRepo.GetByID(id)
}

func (s *RecordService) GetGraphData(patientUserID int) ([]model.HealthRecord, error) {
	patient, err := s.UserRepo.FindPatientByUserID(patientUserID)
	if err != nil {
		return nil, errors.New("patient not found")
	}
	return s.RecordRepo.GetByPatientID(patient.ID)
}

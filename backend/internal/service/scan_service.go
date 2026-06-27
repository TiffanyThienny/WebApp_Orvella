package service

import (
	"errors"

	"backend/internal/model"
	"backend/internal/repository"
	"backend/pkg/ai"
)

type ScanService struct {
	ScanRepo *repository.ScanRepository
	UserRepo *repository.UserRepository
}

func NewScanService(sRepo *repository.ScanRepository, uRepo *repository.UserRepository) *ScanService {
	return &ScanService{ScanRepo: sRepo, UserRepo: uRepo}
}

// UploadScan creates a new scan record with status "uploaded"
func (s *ScanService) UploadScan(userID int, patientID int, imageURL string) (*model.CTScan, error) {
	scan := &model.CTScan{
		PatientID:  patientID,
		UploadedBy: userID,
		ImageURL:   imageURL,
		Status:     model.ScanStatusUploaded,
	}
	err := s.ScanRepo.CreateScan(scan)
	return scan, err
}

// AnalyzeScan triggers AI analysis and sets status to "pending_review"
func (s *ScanService) AnalyzeScan(scanID int) (*model.AIResult, error) {
	scan, err := s.ScanRepo.GetScanByID(scanID)
	if err != nil {
		return nil, err
	}

	// Update to ai_processing first
	_ = s.ScanRepo.UpdateStatus(scanID, model.ScanStatusAIProcessing)

	// Call AI service
	result := ai.AnalyzeScanWithFallback(scanID, scan.ImageURL)

	// After AI completes (or fails), set to pending_review for doctor to review
	err = s.ScanRepo.UpdateStatus(scanID, model.ScanStatusPendingReview)
	if err != nil {
		return nil, err
	}

	if result != nil {
		result.ScanID = scanID
		err = s.ScanRepo.SaveAIResult(result)
		return result, err
	}

	return nil, nil
}

// AssignDoctor assigns a doctor to the scan (does not change status)
func (s *ScanService) AssignDoctor(scanID int, doctorID int) error {
	return s.ScanRepo.AssignDoctor(scanID, doctorID)
}

// SubmitDiagnosis creates a diagnosis draft
func (s *ScanService) SubmitDiagnosis(diag *model.Diagnosis) error {
	diag.Status = model.DiagnosisStatusDraft
	return s.ScanRepo.CreateDiagnosis(diag)
}

// ApproveDiagnosis approves the diagnosis and sets scan status to "approved"
func (s *ScanService) ApproveDiagnosis(diagID int, scanID int) error {
	err := s.ScanRepo.UpdateDiagnosisStatus(diagID, model.DiagnosisStatusApproved)
	if err != nil {
		return err
	}
	return s.ScanRepo.UpdateStatus(scanID, model.ScanStatusApproved)
}

// RejectScan sets the scan status to "rejected"
func (s *ScanService) RejectScan(scanID int) error {
	return s.ScanRepo.UpdateStatus(scanID, model.ScanStatusRejected)
}

// GetScansByPatientUser returns only approved scans for a patient
func (s *ScanService) GetScansByPatientUser(userID int) ([]model.CTScan, error) {
	patient, err := s.UserRepo.FindPatientByUserID(userID)
	if err != nil {
		return nil, errors.New("patient profile not found")
	}
	allScans, err := s.ScanRepo.GetScansByPatient(patient.ID)
	if err != nil {
		return nil, err
	}

	var approvedScans []model.CTScan
	for _, sc := range allScans {
		if sc.Status == model.ScanStatusApproved {
			approvedScans = append(approvedScans, sc)
		}
	}
	if approvedScans == nil {
		approvedScans = []model.CTScan{}
	}
	return approvedScans, nil
}

func (s *ScanService) GetScansByDoctor(doctorID int) ([]model.CTScan, error) {
	return s.ScanRepo.GetScansByDoctor(doctorID)
}

func (s *ScanService) GetAllScans() ([]model.CTScan, error) {
	return s.ScanRepo.GetAllScans()
}

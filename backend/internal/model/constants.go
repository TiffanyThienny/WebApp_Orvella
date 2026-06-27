package model

// Scan Status Constants — single source of truth for scan lifecycle
const (
	ScanStatusUploaded      = "uploaded"
	ScanStatusAIProcessing  = "ai_processing"
	ScanStatusPendingReview = "pending_review"
	ScanStatusApproved      = "approved"
	ScanStatusRejected      = "rejected"
)

// Appointment Status Constants
const (
	AppointmentStatusPending   = "pending"
	AppointmentStatusApproved  = "approved"
	AppointmentStatusRejected  = "rejected"
	AppointmentStatusCompleted = "completed"
)

// Diagnosis Status Constants
const (
	DiagnosisStatusDraft    = "draft"
	DiagnosisStatusApproved = "approved"
)

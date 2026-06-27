package model

import "time"

type Role struct {
	ID   int    `json:"id"`
	Name string `json:"name"`
}

type User struct {
	ID           int       `json:"id"`
	RoleID       int       `json:"role_id"`
	RoleName     string    `json:"role_name,omitempty"`
	Username     string    `json:"username"`
	Email        string    `json:"email,omitempty"`
	PasswordHash string    `json:"-"`
	Token        string    `json:"token,omitempty"`
	FullName     string    `json:"full_name"`
	Phone             string    `json:"phone,omitempty"`
	Address           string    `json:"address,omitempty"`
	ProfileImage      string    `json:"profile_image,omitempty"`
	Specialty         string    `json:"specialty,omitempty"`
	IsProfileComplete bool      `json:"is_profile_complete"`
	CreatedAt         time.Time `json:"created_at"`
}

type SiteConfig struct {
	ID          int       `json:"id"`
	ConfigKey   string    `json:"config_key"`
	ConfigValue string    `json:"config_value"`
	UpdatedAt   time.Time `json:"updated_at"`
}

type Patient struct {
	ID      int     `json:"id"`
	UserID  int     `json:"user_id"`
	User    *User   `json:"user,omitempty"`
	DOB     string  `json:"dob"`
	Gender  string  `json:"gender"`
	Phone            string  `json:"phone"`
	Address          string  `json:"address"`
	MedicalHistory   string  `json:"medical_history,omitempty"`
	Allergies        string  `json:"allergies,omitempty"`
	EmergencyContact string  `json:"emergency_contact,omitempty"`
}

type CTScan struct {
	ID         int       `json:"id"`
	PatientID  int       `json:"patient_id"`
	Patient    *Patient  `json:"patient,omitempty"`
	UploadedBy int       `json:"uploaded_by"`
	DoctorID   *int      `json:"doctor_id,omitempty"`
	Doctor     *User     `json:"doctor,omitempty"`
	ImageURL   string    `json:"image_url"`
	Status     string    `json:"status"` // uploaded, ai_processing, pending_review, approved, rejected
	CreatedAt  time.Time `json:"created_at"`
	AIResult   *AIResult `json:"ai_result,omitempty"`
	Diagnosis  *Diagnosis `json:"diagnosis,omitempty"`
}

type AIResult struct {
	ID               int     `json:"id"`
	ScanID           int     `json:"scan_id"`
	PredictionLabel  string  `json:"prediction_label"` // e.g. "Normal", "Tumor Detected"
	ResultText       string  `json:"result_text"`
	Confidence       float64 `json:"confidence"`
	RiskLevel        string  `json:"risk_level"`
	AnalyzedImageURL string  `json:"analyzed_image_url"`
}

type Diagnosis struct {
	ID        int    `json:"id"`
	ScanID    int    `json:"scan_id"`
	DoctorID  int    `json:"doctor_id"`
	Notes     string `json:"notes"`
	Status    string `json:"status"` // draft, approved
}

type HealthRecord struct {
	ID          int       `json:"id"`
	PatientID   int       `json:"patient_id"`
	ScanID      int       `json:"scan_id"`
	CreatedBy    int       `json:"created_by"`
	Systolic     int       `json:"systolic"`
	Diastolic    int       `json:"diastolic"`
	HeartRate    int       `json:"heart_rate"`
	Temperature  float32   `json:"temperature"`
	OxygenLevel  int       `json:"oxygen_level"`
	Weight       float32   `json:"weight"`
	HealthScore  int       `json:"health_score"`
	AlertStatus  string    `json:"alert_status"`
	AlertMessage string    `json:"alert_message"`
	Notes        string    `json:"notes"`
	CreatedAt    time.Time `json:"created_at"`
}

type Appointment struct {
	ID              int       `json:"id"`
	PatientID       int       `json:"patient_id"`
	PatientName     string    `json:"patient_name,omitempty"`
	DoctorID        int       `json:"doctor_id"`
	DoctorName      string    `json:"doctor_name,omitempty"`
	AppointmentDate time.Time `json:"appointment_date"`
	Notes           string    `json:"notes"`
	Status          string    `json:"status"` // pending, approved, rejected, completed
	CreatedAt       time.Time `json:"created_at"`
}

type Schedule struct {
	ID              int       `json:"id"`
	DoctorID        int       `json:"doctor_id"`
	DoctorName      string    `json:"doctor_name,omitempty"`
	DayOfWeek       string    `json:"day_of_week"`
	AppointmentDate *string   `json:"appointment_date,omitempty"`
	EndDate         *string   `json:"end_date,omitempty"`
	StartTime       string    `json:"start_time"`
	EndTime         string    `json:"end_time"`
	MaxPatients     int       `json:"max_patients"`
	IsAvailable     bool      `json:"is_available"`
	BookedCount     int       `json:"booked_count"`
	CreatedAt       time.Time `json:"created_at"`
}

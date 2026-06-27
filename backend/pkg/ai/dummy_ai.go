package ai

import (
	"math/rand"
	"time"

	"backend/internal/model"
)

func AnalyzeScan(scanID int) *model.AIResult {
	// Simulate delay
	time.Sleep(3 * time.Second)

	results := []struct {
		Label          string
		Result         string
		Recommendation string
	}{
		{"metaplastic", "Signs of metaplastic cell transformation (Mock).", "Schedule immediate secondary screening."},
		{"superficial_intermediate", "No abnormalities detected in the CT scan (Mock).", "Routine checkup next year."},
		{"Koilocytotic", "Koilocytotic changes detected, typical of HPV infection (Mock).", "Consult with a specialist for HPV-related follow-up."},
		{"dsykeratotic", "Dyskeratotic cells observed, indicating potential pathology (Mock).", "Urgent biopsy recommended."},
		{"Parabasal", "Small parabasal cell abnormalities detected (Mock).", "Schedule a follow-up scan in 3 months."},
	}

	rand.Seed(time.Now().UnixNano())
	chosen := results[rand.Intn(len(results))]
	confidence := 0.70 + rand.Float64()*(0.99-0.70)

	return &model.AIResult{
		ScanID:          scanID,
		PredictionLabel: chosen.Label,
		ResultText:      chosen.Result,
		Confidence:      confidence,
		RiskLevel:       chosen.Recommendation,
	}
}

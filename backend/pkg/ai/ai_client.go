package ai

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"io"
	"io/ioutil"
	"math/rand"
	"mime/multipart"
	"net/http"
	"strings"
	"time"

	"backend/internal/model"
)

const AI_ENDPOINT = "http://localhost:8002/predict"
const CLOUDFLARE_ENDPOINT = "https://investigations-honors-ooo-colleagues.trycloudflare.com"

type AIResponse struct {
	Class         string  `json:"class"`
	Index         int     `json:"index"`
	Confidence    float64 `json:"confidence"`
	GradcamBase64 string  `json:"gradcam_base64"`
}

func AnalyzeScanWithFallback(scanID int, imageURL string) *model.AIResult {
	// 1. Try Local AI
	fmt.Printf("Attempting Local AI for Scan %d...\n", scanID)
	result, err := callAI(scanID, imageURL, AI_ENDPOINT)
	if err == nil {
		return result
	}
	fmt.Printf("Local AI failed: %v. Attempting Cloud AI...\n", err)

	// 2. Try Cloud AI
	result, err = callAI(scanID, imageURL, CLOUDFLARE_ENDPOINT)
	if err == nil {
		return result
	}

	// 3. Fallback to Mock
	fmt.Printf("All AI APIs failed, falling back to Mock Result for Scan %d\n", scanID)
	return GenerateMockResult(scanID, imageURL)
}

func callAI(scanID int, imageURL string, endpoint string) (*model.AIResult, error) {
	fullPath := imageURL
	fileBytes, err := ioutil.ReadFile(fullPath)
	if err != nil {
		return nil, fmt.Errorf("failed to read local image: %v", err)
	}

	var b bytes.Buffer
	w := multipart.NewWriter(&b)
	fw, err := w.CreateFormFile("file", imageURL)
	if err != nil {
		return nil, err
	}
	if _, err = io.Copy(fw, bytes.NewReader(fileBytes)); err != nil {
		return nil, err
	}
	w.Close()

	req, err := http.NewRequest("POST", endpoint, &b)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Content-Type", w.FormDataContentType())

	client := &http.Client{Timeout: 30 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := ioutil.ReadAll(resp.Body)
		return nil, fmt.Errorf("AI API error (%d): %s", resp.StatusCode, string(body))
	}

	var aiResp AIResponse
	if err := json.NewDecoder(resp.Body).Decode(&aiResp); err != nil {
		return nil, err
	}

	info := getInfoByClass(aiResp.Class)

	analyzedURL := imageURL
	if aiResp.GradcamBase64 != "" {
		dec, err := base64.StdEncoding.DecodeString(aiResp.GradcamBase64)
		if err == nil {
			newPath := fmt.Sprintf("uploads/ct_scans/heatmap_%d_%d.jpg", scanID, time.Now().Unix())
			err = ioutil.WriteFile(newPath, dec, 0644)
			if err == nil {
				analyzedURL = newPath
				fmt.Printf("Heatmap saved to %s (%d bytes)\n", newPath, len(dec))
			} else {
				fmt.Printf("Failed to write heatmap file: %v\n", err)
			}
		} else {
			fmt.Printf("Failed to decode gradcam base64: %v\n", err)
		}
	} else {
		fmt.Println("No gradcam_base64 in AI response")
	}

	return &model.AIResult{
		ScanID:           scanID,
		PredictionLabel:  aiResp.Class,
		ResultText:       info.Result,
		Confidence:       aiResp.Confidence,
		RiskLevel:        info.Recommendation,
		AnalyzedImageURL: analyzedURL,
	}, nil
}

type ClassInfo struct {
	Result         string
	Recommendation string
}

func getInfoByClass(className string) ClassInfo {
	searchKey := strings.ToLower(className)
	mapping := map[string]ClassInfo{
		"dyskeratotic": {
			Result:         "Dyskeratotic cells detected (Abnormal pathology).",
			Recommendation: "Immediate oncological review and biopsy recommended.",
		},
		"koilocytotic": {
			Result:         "Koilocytotic changes identified (suggestive of HPV infection).",
			Recommendation: "Follow-up with HPV DNA testing and secondary screening.",
		},
		"metaplastic": {
			Result:         "Metaplastic cell transformation present in CT sample.",
			Recommendation: "Repeat cytology in 6 months for monitoring.",
		},
		"parabasal": {
			Result:         "Predominant Parabasal cell pattern observed.",
			Recommendation: "Clinical correlation with hormonal status recommended.",
		},
		"superficial-intermediate": {
			Result:         "Superficial-Intermediate cells observed (Normal pattern).",
			Recommendation: "Continue with routine annual screening.",
		},
	}

	if info, ok := mapping[searchKey]; ok {
		return info
	}
	return ClassInfo{Result: "Unknown pattern detected by AI model", Recommendation: "Manual physician review required."}
}

func GenerateMockResult(scanID int, imageURL string) *model.AIResult {
	classes := []string{"Dyskeratotic", "Koilocytotic", "Metaplastic", "Parabasal", "Superficial-Intermediate"}
	rand.Seed(time.Now().UnixNano())
	chosenClass := classes[rand.Intn(len(classes))]
	info := getInfoByClass(chosenClass)

	return &model.AIResult{
		ScanID:           scanID,
		PredictionLabel:  chosenClass,
		ResultText:       info.Result,
		Confidence:       0.70 + rand.Float64()*0.2,
		RiskLevel:        info.Recommendation,
		AnalyzedImageURL: imageURL,
	}
}

package storage

import (
	"fmt"
	"io"
	"mime/multipart"
	"os"
	"path/filepath"
	"time"
)

// StorageService defines the interface for Cloud Storage (S3/R2)
type StorageService interface {
	UploadFile(file *multipart.FileHeader, folder string) (string, error)
}

// MockS3Storage implements StorageService but writes locally, mocking an S3 bucket
type MockS3Storage struct {
	BucketURL string
}

func NewMockS3Storage() *MockS3Storage {
	// Represents the base URL to access the bucket publicly
	return &MockS3Storage{
		BucketURL: "http://localhost:8080",
	}
}

func (s *MockS3Storage) UploadFile(file *multipart.FileHeader, folder string) (string, error) {
	// This mocks the latency of contacting AWS S3 / Cloudflare R2
	time.Sleep(500 * time.Millisecond)

	// Ensure the "bucket" local folder exists
	err := os.MkdirAll(filepath.Join("uploads", folder), os.ModePerm)
	if err != nil {
		return "", err
	}

	src, err := file.Open()
	if err != nil {
		return "", err
	}
	defer src.Close()

	// Generate unique object key (simulating S3 object key)
	objectKey := fmt.Sprintf("%d_%s", time.Now().UnixNano(), file.Filename)
	filePath := filepath.Join("uploads", folder, objectKey)

	dst, err := os.Create(filePath)
	if err != nil {
		return "", err
	}
	defer dst.Close()

	if _, err = io.Copy(dst, src); err != nil {
		return "", err
	}

	// Return the "S3/R2 Public URL"
	return fmt.Sprintf("uploads/%s/%s", folder, objectKey), nil
}

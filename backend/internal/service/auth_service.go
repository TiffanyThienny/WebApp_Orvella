package service

import (
	"errors"

	"backend/internal/model"
	"backend/internal/repository"
	"backend/pkg/utils"
)

type AuthService struct {
	Repo *repository.UserRepository
}

// ✅ constructor harus ditutup dengan benar
func NewAuthService(repo *repository.UserRepository) *AuthService {
	return &AuthService{
		Repo: repo,
	}
}

// ✅ function harus berdiri sendiri (tidak di dalam function lain)
func (s *AuthService) Register(user *model.User, password string, profile *model.Patient) (string, error) {
	hash, err := utils.HashPassword(password)
	if err != nil {
		return "", err
	}
	user.PasswordHash = hash
	
	err = s.Repo.CreateUser(user, profile)
	if err != nil {
		return "", err
	}

	var roleName string
	err = s.Repo.DB.QueryRow("SELECT name FROM roles WHERE id = ?", user.RoleID).Scan(&roleName)
	if err != nil {
		return "", err
	}

	token, err := utils.GenerateJWT(user.ID, user.RoleID, roleName, user.Username)
	if err != nil {
		return "", err
	}

	// Still update DB for fallback compatibility if needed
	_ = s.Repo.UpdateToken(user.ID, token)

	return token, nil
}

func (s *AuthService) Login(username, password string) (string, error) {
	user, err := s.Repo.FindByUsername(username)
	if err != nil {
		return "", errors.New("invalid credentials")
	}

	if !utils.CheckPasswordHash(password, user.PasswordHash) {
		return "", errors.New("invalid credentials")
	}

	token, err := utils.GenerateJWT(user.ID, user.RoleID, user.RoleName, user.Username)
	if err != nil {
		return "", err
	}

	// Still update DB for fallback compatibility
	_ = s.Repo.UpdateToken(user.ID, token)

	return token, nil
}

func (s *AuthService) Logout(userID int) error {
	return s.Repo.UpdateToken(userID, "")
}

func (s *AuthService) GetAllUsers() ([]model.User, error) {
	return s.Repo.GetAllUsers()
}

func (s *AuthService) CheckUserExists(username string) (*model.User, error) {
	return s.Repo.FindByUsername(username)
}

func (s *AuthService) ResetPassword(userID int, newPassword string) error {
	hash, err := utils.HashPassword(newPassword)
	if err != nil {
		return err
	}
	return s.Repo.UpdatePassword(userID, hash)
}

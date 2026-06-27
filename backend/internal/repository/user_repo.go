package repository

import (
	"database/sql"
	"errors"

	"backend/internal/model"
)

type UserRepository struct {
	DB *sql.DB
}

func NewUserRepository(db *sql.DB) *UserRepository {
	return &UserRepository{DB: db}
}

func (r *UserRepository) FindByUsername(username string) (*model.User, error) {
	var user model.User
	err := r.DB.QueryRow(`
		SELECT u.id, u.role_id, r.name, u.username, IFNULL(u.email, ''), u.password_hash, u.full_name, IFNULL(u.phone, ''), IFNULL(u.address, ''), IFNULL(u.profile_image, ''), IFNULL(u.is_profile_complete, 0)
		FROM users u
		JOIN roles r ON u.role_id = r.id
		WHERE u.username = ?`, username).Scan(&user.ID, &user.RoleID, &user.RoleName, &user.Username, &user.Email, &user.PasswordHash, &user.FullName,
		&user.Phone, &user.Address, &user.ProfileImage, &user.IsProfileComplete)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, errors.New("user not found")
		}
		return nil, err
	}
	return &user, nil
}

func (r *UserRepository) CreateUser(user *model.User, profile *model.Patient) error {
	tx, err := r.DB.Begin()
	if err != nil {
		return err
	}

	isComplete := false
	if user.RoleID == 1 { // Admin
		isComplete = true
	} else if user.RoleID == 3 && profile != nil { // Patient
		isComplete = true
	} else if user.RoleID == 2 && user.Specialty != "" { // Doctor with specialty
		isComplete = true
	}

	res, err := tx.Exec("INSERT INTO users (role_id, username, email, password_hash, full_name, phone, address, specialty, is_profile_complete) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
		user.RoleID, user.Username, user.Email, user.PasswordHash, user.FullName, user.Phone, user.Address, user.Specialty, isComplete)
	if err != nil {
		tx.Rollback()
		return err
	}
	id, err := res.LastInsertId()
	if err != nil {
		tx.Rollback()
		return err
	}
	user.ID = int(id)

	if user.RoleID == 3 {
		if profile != nil {
			_, err = tx.Exec("INSERT INTO patients (user_id, dob, gender, phone, address, medical_history, allergies, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
				user.ID, profile.DOB, profile.Gender, user.Phone, user.Address, profile.MedicalHistory, profile.Allergies, profile.EmergencyContact)
		} else {
			_, err = tx.Exec("INSERT INTO patients (user_id, phone, address) VALUES (?, ?, ?)", user.ID, user.Phone, user.Address)
		}
		
		if err != nil {
			tx.Rollback()
			return err
		}
	}

	return tx.Commit()
}

func (r *UserRepository) UpdateToken(userID int, token string) error {
	_, err := r.DB.Exec("UPDATE users SET token = ? WHERE id = ?", token, userID)
	return err
}

func (r *UserRepository) UpdatePassword(userID int, passwordHash string) error {
	_, err := r.DB.Exec("UPDATE users SET password_hash = ? WHERE id = ?", passwordHash, userID)
	return err
}

func (r *UserRepository) FindPatientByUserID(userID int) (*model.Patient, error) {
	var p model.Patient
	err := r.DB.QueryRow(`SELECT id, user_id, IFNULL(dob, ''), IFNULL(gender, ''), IFNULL(phone, ''), IFNULL(address, ''), IFNULL(medical_history, ''), IFNULL(allergies, ''), IFNULL(emergency_contact, '') FROM patients WHERE user_id = ?`, userID).
		Scan(&p.ID, &p.UserID, &p.DOB, &p.Gender, &p.Phone, &p.Address, &p.MedicalHistory, &p.Allergies, &p.EmergencyContact)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, errors.New("patient not found")
		}
		return nil, err
	}
	return &p, nil
}

func (r *UserRepository) GetAllUsers() ([]model.User, error) {
	rows, err := r.DB.Query(`
		SELECT u.id, u.role_id, r.name, u.username, IFNULL(u.email, ''), u.full_name, IFNULL(u.phone, ''), IFNULL(u.address, ''), IFNULL(u.profile_image, ''), IFNULL(u.is_profile_complete, 0), u.created_at, IFNULL(u.specialty, '')
		FROM users u
		JOIN roles r ON u.role_id = r.id
	`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var users []model.User
	for rows.Next() {
		var user model.User
		if err := rows.Scan(&user.ID, &user.RoleID, &user.RoleName, &user.Username, &user.Email, &user.FullName, &user.Phone, &user.Address, &user.ProfileImage, &user.IsProfileComplete, &user.CreatedAt, &user.Specialty); err != nil {
			return nil, err
		}
		users = append(users, user)
	}
	return users, nil
}

func (r *UserRepository) GetAllPatients() ([]model.Patient, error) {
	rows, err := r.DB.Query(`
		SELECT p.id, p.user_id, u.full_name, IFNULL(p.dob, ''), IFNULL(p.gender, ''), IFNULL(p.phone, ''), IFNULL(p.address, ''),
		       IFNULL(p.medical_history, ''), IFNULL(p.allergies, ''), IFNULL(p.emergency_contact, '')
		FROM patients p
		JOIN users u ON p.user_id = u.id
	`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var patients []model.Patient
	for rows.Next() {
		var p model.Patient
		var u model.User
		if err := rows.Scan(&p.ID, &p.UserID, &u.FullName, &p.DOB, &p.Gender, &p.Phone, &p.Address, &p.MedicalHistory, &p.Allergies, &p.EmergencyContact); err != nil {
			return nil, err
		}
		p.User = &u
		patients = append(patients, p)
	}
	return patients, nil
}

USE fp4;

-- Admin
INSERT INTO users (role_id, username, email, password_hash, full_name, phone, address, is_profile_complete) 
VALUES (1, 'admin', 'admin@example.com', '$2y$10$hgKb4oKdFGOXqztJALOeb.0SzNj3pd/TZs7adFlRmGBquaKtaN0Vm', 'System Admin', '1234567890', 'Admin HQ', TRUE);

-- Doctor
INSERT INTO users (role_id, username, email, password_hash, full_name, phone, address, specialty, is_profile_complete) 
VALUES (2, 'doctor', 'doctor@example.com', '$2y$10$hgKb4oKdFGOXqztJALOeb.0SzNj3pd/TZs7adFlRmGBquaKtaN0Vm', 'Dr. Smith', '0987654321', 'Hospital St', 'Radiologist', TRUE);

-- Patient
INSERT INTO users (role_id, username, email, password_hash, full_name, phone, address, is_profile_complete) 
VALUES (3, 'patient', 'patient@example.com', '$2y$10$hgKb4oKdFGOXqztJALOeb.0SzNj3pd/TZs7adFlRmGBquaKtaN0Vm', 'John Doe', '1112223333', 'Patient Home', TRUE);

-- Insert into patients table for the patient user
INSERT INTO patients (user_id, dob, gender, phone, address, medical_history, allergies, emergency_contact) 
VALUES (LAST_INSERT_ID(), '1990-01-01', 'Male', '1112223333', 'Patient Home', 'None', 'None', 'Jane Doe');

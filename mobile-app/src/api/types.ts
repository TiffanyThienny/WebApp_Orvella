/**
 * Standardized response types matching the Go backend's
 * utils.SuccessResponse / utils.ErrorResponse / utils.PaginatedResponse shape.
 */

export interface ApiSuccess<T = unknown> {
  success: true;
  message: string;
  data: T;
}

export interface ApiError {
  success: false;
  message: string;
  errors?: unknown;
}

export type ApiResponse<T = unknown> = ApiSuccess<T> | ApiError;

export interface PaginatedMeta {
  page: number;
  limit: number;
  total: number;
  total_pages: number;
}

export interface PaginatedSuccess<T = unknown> {
  success: true;
  message: string;
  data: T[];
  meta: PaginatedMeta;
}

// ─── Domain Types ─────────────────────────────────────────────────────────────

export interface User {
  id: number;
  username: string;
  full_name: string;
  email: string;
  phone: string;
  address: string;
  profile_image: string;
  role_id: number;
  role_name: string;
  is_profile_complete: boolean;
  created_at: string;
}

export interface Patient {
  id: number;
  user_id: number;
  name: string;
  dob: string;
  phone: string;
  profile_image: string;
  status: 'normal' | 'warning' | 'critical';
  gender: string;
  emergency_contact: string;
}

export interface CTScan {
  id: number;
  patient_id: number;
  doctor_id: number | null;
  image_url: string;
  status: 'pending' | 'analyzed' | 'reviewed' | 'approved';
  created_at: string;
  ai_result?: AIResult;
  diagnosis?: Diagnosis;
  patient_name?: string;
  doctor?: User;
}

export interface AIResult {
  prediction_label: string;
  confidence: number;
  risk_level: 'low' | 'moderate' | 'high';
}

export interface Diagnosis {
  id: number;
  scan_id: number;
  doctor_id: number;
  notes: string;
  created_at: string;
}

export interface Appointment {
  id: number;
  patient_id: number;
  doctor_id: number;
  appointment_date: string;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'approved';
  notes: string;
  doctor_name?: string;
  patient_name?: string;
}

export interface HealthRecord {
  id: number;
  patient_id: number;
  systolic: number;
  diastolic: number;
  heart_rate: number;
  temperature: number;
  oxygen_level: number;
  weight: number;
  health_score: number;
  alert_status: 'normal' | 'warning' | 'critical';
  notes: string;
  created_at: string;
}

export interface AuthTokens {
  token: string;
  refresh_token?: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}

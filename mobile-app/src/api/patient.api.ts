import apiClient from './client';
import type { ApiSuccess, Patient, HealthRecord } from './types';

export const patientApi = {
  // Medrec — get all patients
  getPatients: async () => {
    const res = await apiClient.get<ApiSuccess<Patient[]>>('/patients');
    return res.data;
  },

  // Medrec — get a single patient's full detail (profile + scans + records)
  getPatientDetail: async (patientId: number) => {
    const res = await apiClient.get<
      ApiSuccess<{ profile: Patient; scans: any[]; records: HealthRecord[] }>
    >(`/patients/${patientId}`);
    return res.data;
  },

  // Medrec — update patient clinical info
  updatePatient: async (
    patientId: number,
    payload: Partial<{
      name: string;
      phone: string;
      address: string;
      dob: string;
      medical_history: string;
      allergies: string;
    }>
  ) => {
    const res = await apiClient.put<ApiSuccess<null>>(
      `/patients/${patientId}`,
      payload
    );
    return res.data;
  },

  // Patient — get own health records / graph data
  getHealthRecords: async (patientUserId?: number) => {
    const res = await apiClient.get<ApiSuccess<HealthRecord[]>>('/health-records/graph', {
      params: patientUserId ? { patient_user_id: patientUserId } : undefined,
    });
    return res.data;
  },

  // Patient — get own profile
  getProfile: async () => {
    const res = await apiClient.get<ApiSuccess<Patient>>('/profile');
    return res.data;
  },

  // Patient — get list of doctors for appointment booking
  getDoctors: async () => {
    const res = await apiClient.get<ApiSuccess<any[]>>('/doctors');
    return res.data;
  },

  // Medrec — create a health record / vitals for a patient
  createHealthRecord: async (payload: {
    patient_id: number;
    scan_id?: number;
    systolic: number;
    diastolic: number;
    heart_rate: number;
    weight: number;
    oxygen_level: number;
    temperature: number;
    notes: string;
  }) => {
    const res = await apiClient.post<ApiSuccess<null>>('/health-records', payload);
    return res.data;
  },
};

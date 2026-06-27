import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { authApi } from '../api/auth.api';
import { appointmentApi } from '../api/appointment.api';
import { scanApi } from '../api/scan.api';
import { patientApi } from '../api/patient.api';
import { queryKeys } from '../api/queryClient';

// ─── Auth ─────────────────────────────────────────────────────────────────────
export const useMe = () =>
  useQuery({
    queryKey: queryKeys.me,
    queryFn: () => authApi.getMe(),
    select: (res) => (res.success ? res.data.user : null),
  });

// ─── Appointments ─────────────────────────────────────────────────────────────
export const useAppointments = () =>
  useQuery({
    queryKey: queryKeys.appointments,
    queryFn: () => appointmentApi.getMyAppointments(),
    select: (res) => (res.success ? res.data : []),
  });

export const useBookAppointment = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: {
      doctor_id: number;
      appointment_date: string;
      notes?: string;
    }) => appointmentApi.bookAppointment(payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.appointments }),
  });
};

export const useCancelAppointment = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => appointmentApi.cancelAppointment(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.appointments }),
  });
};

// ─── Doctor Schedules ─────────────────────────────────────────────────────────
export const useDoctorSchedules = (doctorId: number | null) =>
  useQuery({
    queryKey: queryKeys.doctorSchedules(doctorId ?? 0),
    queryFn: () => appointmentApi.getSchedulesByDoctor(doctorId!),
    enabled: !!doctorId,
    select: (res) => (res.success ? res.data : []),
  });

// ─── Scans ────────────────────────────────────────────────────────────────────
export const useScans = (params?: { page?: number; limit?: number; status?: string }) =>
  useQuery({
    queryKey: queryKeys.scans(params),
    queryFn: () => scanApi.getMyScans(params),
    select: (res) => (res.success ? { data: res.data, meta: res.meta } : { data: [], meta: null }),
  });

export const useAssignDoctor = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ scanId, doctorId }: { scanId: number; doctorId: number }) =>
      scanApi.assignDoctor(scanId, doctorId),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.scans() }),
  });
};

export const useAnalyzeScan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (scanId: number) => scanApi.analyzeScan(scanId),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.scans() }),
  });
};

// ─── Patients (Medrec) ────────────────────────────────────────────────────────
export const usePatients = () =>
  useQuery({
    queryKey: queryKeys.patients,
    queryFn: () => patientApi.getPatients(),
    select: (res) => (res.success ? res.data : []),
  });

export const usePatientDetail = (patientId: number) =>
  useQuery({
    queryKey: queryKeys.patientDetail(patientId),
    queryFn: () => patientApi.getPatientDetail(patientId),
    enabled: !!patientId,
    select: (res) => (res.success ? res.data : null),
  });

// ─── Health Records ───────────────────────────────────────────────────────────
export const useHealthRecords = (userId?: number) =>
  useQuery({
    queryKey: queryKeys.healthRecords(userId),
    queryFn: () => patientApi.getHealthRecords(userId),
    select: (res) => (res.success ? res.data : []),
  });

// ─── Doctors ──────────────────────────────────────────────────────────────────
export const useDoctors = () =>
  useQuery({
    queryKey: queryKeys.doctors,
    queryFn: () => patientApi.getDoctors(),
    select: (res) => (res.success ? res.data : []),
  });

// ─── Medrec Workflow ──────────────────────────────────────────────────────────
export const useTriggerAi = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (scanId: number) => scanApi.analyzeScan(scanId),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.scans() }),
  });
};

export const useUploadScanWorkflow = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      patientId: number;
      doctorId: number;
      imageFormData: FormData;
      vitals: {
        systolic: number;
        diastolic: number;
        heart_rate: number;
        weight: number;
        oxygen_level: number;
        temperature: number;
        notes: string;
      };
    }) => {
      // Step 1: Upload scan image
      const uploadRes = await scanApi.uploadScan(payload.imageFormData);
      if (!uploadRes.success || !uploadRes.data?.id) {
        throw new Error(uploadRes.message || 'Failed to upload CT Scan image');
      }
      const scanId = uploadRes.data.id;

      // Step 2: Assign Doctor
      await scanApi.assignDoctor(scanId, payload.doctorId);

      // Step 3: Trigger AI Analysis (async on server)
      await scanApi.analyzeScan(scanId);

      // Step 4: Create Health Record — linked to the scan via scan_id
      await patientApi.createHealthRecord({
        patient_id: payload.patientId,
        scan_id: scanId,
        systolic: payload.vitals.systolic,
        diastolic: payload.vitals.diastolic,
        heart_rate: payload.vitals.heart_rate,
        weight: payload.vitals.weight,
        oxygen_level: payload.vitals.oxygen_level,
        temperature: payload.vitals.temperature,
        notes: payload.vitals.notes,
      });

      return uploadRes;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.scans() });
      qc.invalidateQueries({ queryKey: queryKeys.patients });
    },
  });
};

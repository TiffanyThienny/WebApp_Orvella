import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Data stays fresh for 2 minutes
      staleTime: 2 * 60 * 1000,
      // Cache data for 5 minutes after component unmounts
      gcTime: 5 * 60 * 1000,
      // Retry once on failure (useful for flaky mobile networks)
      retry: 1,
      retryDelay: 1500,
      // Refetch when app comes to foreground
      refetchOnWindowFocus: true,
    },
    mutations: {
      retry: 0,
    },
  },
});

// ─── Query Key Factory ────────────────────────────────────────────────────────
// Centralised keys prevent typos & make invalidation easy.

export const queryKeys = {
  // Auth
  me: ['me'] as const,

  // Appointments
  appointments: ['appointments'] as const,

  // Scans
  scans: (params?: Record<string, unknown>) =>
    params ? ['scans', params] : ['scans'],
  scanDetail: (id: number) => ['scans', id] as const,

  // Patients (Medrec)
  patients: ['patients'] as const,
  patientDetail: (id: number) => ['patients', id] as const,

  // Health records / graph
  healthRecords: (userId?: number) =>
    userId ? ['health-records', userId] : ['health-records'],

  // Doctors list
  doctors: ['doctors'] as const,

  // Schedules
  schedules: ['schedules'] as const,
  doctorSchedules: (doctorId: number) => ['schedules', doctorId] as const,
};

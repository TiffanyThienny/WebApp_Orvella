import apiClient from './client';
import type { ApiSuccess, Appointment } from './types';

export const appointmentApi = {
  getMyAppointments: async () => {
    const res = await apiClient.get<ApiSuccess<Appointment[]>>('/appointments');
    return res.data;
  },

  bookAppointment: async (payload: {
    doctor_id: number;
    appointment_date: string;
    notes?: string;
  }) => {
    const res = await apiClient.post<ApiSuccess<Appointment>>(
      '/appointments',
      payload
    );
    return res.data;
  },

  cancelAppointment: async (appointmentId: number) => {
    const res = await apiClient.put<ApiSuccess<null>>(
      `/appointments/${appointmentId}/cancel`,
      {}
    );
    return res.data;
  },

  getSchedulesByDoctor: async (doctorId: number) => {
    const res = await apiClient.get<ApiSuccess<any[]>>(`/schedules/${doctorId}`);
    return res.data;
  },
};


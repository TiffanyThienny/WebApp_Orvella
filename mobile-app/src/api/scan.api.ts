import axios from 'axios';
import { storage } from '../utils/storage';
import apiClient, { BASE_URL } from './client';
import type { ApiSuccess, CTScan, PaginatedSuccess } from './types';

export const scanApi = {
  getMyScans: async (params?: { page?: number; limit?: number; status?: string }) => {
    const res = await apiClient.get<PaginatedSuccess<CTScan>>('/scans', {
      params,
    });
    return res.data;
  },

  getScanDetail: async (scanId: number) => {
    const res = await apiClient.get<ApiSuccess<CTScan>>(`/scans/${scanId}`);
    return res.data;
  },

  // Medrec only — upload a scan for a patient
  // IMPORTANT: We use raw axios to bypass apiClient's default JSON Content-Type,
  // allowing Axios to correctly auto-generate the multipart/form-data boundary.
  uploadScan: async (formData: FormData) => {
    const token = await storage.getItem('access_token');
    const res = await axios.post<ApiSuccess<CTScan>>(`${BASE_URL}/scans`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        Accept: 'application/json',
        'X-Client-Type': 'mobile',
        Authorization: token ? `Bearer ${token}` : undefined,
      },
    });
    return res.data;
  },

  // Medrec only — assign a doctor to a scan
  assignDoctor: async (scanId: number, doctorId: number) => {
    const res = await apiClient.post<ApiSuccess<null>>(
      `/scans/${scanId}/assign-doctor`,
      { doctor_id: doctorId }
    );
    return res.data;
  },

  // Medrec only — trigger AI analysis
  analyzeScan: async (scanId: number) => {
    const res = await apiClient.post<ApiSuccess<{ scan_id: number }>>(
      `/scans/${scanId}/analyze`,
      {}
    );
    return res.data;
  },
};

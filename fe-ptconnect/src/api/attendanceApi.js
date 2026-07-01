import axiosClient from './axiosClient'

export const attendanceApi = {
  getToday: (params) => axiosClient.get('/attendance/today', { params }),
  getHistory: (params) => axiosClient.get('/attendance/history', { params }),
  getSessions: (params) => axiosClient.get('/attendance/sessions', { params }),
  getSession: (id) => axiosClient.get(`/attendance/sessions/${id}`),
  createSession: (payload) => axiosClient.post('/attendance/sessions', payload),
  createSessionsBulk: (payload) => axiosClient.post('/attendance/sessions/bulk', payload),
  updateSession: (id, payload) => axiosClient.put(`/attendance/sessions/${id}`, payload),
  closeSession: (id) => axiosClient.patch(`/attendance/sessions/${id}/close`),
  deleteSession: (id) => axiosClient.delete(`/attendance/sessions/${id}`),
  submit: (payload) => axiosClient.post('/attendance', payload),
  getParentHistory: () => axiosClient.get('/attendance/parent'),
}

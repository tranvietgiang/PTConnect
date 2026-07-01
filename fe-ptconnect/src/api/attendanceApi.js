import axiosClient from './axiosClient'

export const attendanceApi = {
  getToday: (params) => axiosClient.get('/attendance/today', { params }),
  getHistory: (params) => axiosClient.get('/attendance/history', { params }),
  submit: (payload) => axiosClient.post('/attendance', payload),
  getStudentHistory: (params) => axiosClient.get('/attendance/student', { params }),

  getSessions: (params) => axiosClient.get('/attendance/sessions', { params }),
  getSession: (id) => axiosClient.get(`/attendance/sessions/${id}`),
  createSession: (payload) => axiosClient.post('/attendance/sessions', payload),
  updateSession: (id, payload) => axiosClient.put(`/attendance/sessions/${id}`, payload),
  deleteSession: (id) => axiosClient.delete(`/attendance/sessions/${id}`),
  createSessionsBulk: (payload) => axiosClient.post('/attendance/sessions/bulk', payload),
  closeSession: (id) => axiosClient.post(`/attendance/sessions/${id}/close`),
}

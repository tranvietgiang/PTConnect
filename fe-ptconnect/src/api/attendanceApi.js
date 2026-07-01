import axiosClient from './axiosClient'

export const attendanceApi = {
  getToday: (params) => axiosClient.get('/attendance/today', { params }),
  getHistory: (params) => axiosClient.get('/attendance/history', { params }),
  submit: (payload) => axiosClient.post('/attendance', payload),
  getStudentHistory: (params) => axiosClient.get('/attendance/student', { params }),
}

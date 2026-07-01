import axiosClient from './axiosClient'

export const studentApi = {
  getAll: (params) => axiosClient.get('/students', { params }),
  getById: (id) => axiosClient.get(`/students/${id}`),
  create: (payload) =>
    axiosClient.post('/students', payload, {
      headers: { 'Content-Type': 'application/json' },
    }),
  importExcel: (payload) =>
    axiosClient.post('/students/import', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      loadingLabel: 'Đang import danh sách học sinh',
    }),
}

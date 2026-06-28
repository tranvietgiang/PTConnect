import axiosClient from './axiosClient'

export const studentApi = {
  getAll: (params) => axiosClient.get('/students', { params }),
  getById: (id) => axiosClient.get(`/students/${id}`),
  create: (payload) => axiosClient.post('/students', payload),
  update: (id, payload) => axiosClient.put(`/students/${id}`, payload),
  remove: (id) => axiosClient.delete(`/students/${id}`),
}

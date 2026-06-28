import axiosClient from './axiosClient'

export const classApi = {
  getAll: (params) => axiosClient.get('/classes', { params }),
  getById: (id) => axiosClient.get(`/classes/${id}`),
  create: (payload) => axiosClient.post('/classes', payload),
  update: (id, payload) => axiosClient.put(`/classes/${id}`, payload),
  remove: (id) => axiosClient.delete(`/classes/${id}`),
}

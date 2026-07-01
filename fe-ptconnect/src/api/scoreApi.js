import axiosClient from './axiosClient'

export const scoreApi = {
  getAll: (params) => axiosClient.get('/scores', { params }),
  getReport: (params) => axiosClient.get('/scores/report', { params }),
  getColumns: (params) => axiosClient.get('/score-columns', { params }),
  createColumn: (payload) => axiosClient.post('/score-columns', payload),
  updateColumn: (id, payload) => axiosClient.put(`/score-columns/${id}`, payload),
  saveRecords: (payload) => axiosClient.post('/score-records', payload),
}

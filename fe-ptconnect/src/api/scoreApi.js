import axiosClient from './axiosClient'

export const scoreApi = {
  getAll: (params) => axiosClient.get('/scores', { params }),
  getReport: (params) => axiosClient.get('/scores/report', { params }),
}

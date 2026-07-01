import axiosClient from './axiosClient'

export const emailNotificationApi = {
  getAll: (params) => axiosClient.get('/email-notifications', { params }),
  send: (payload) => axiosClient.post('/email-notifications', payload),
}

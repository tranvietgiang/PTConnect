import axiosClient from './axiosClient'

export const notificationApi = {
  getAll: (params) => axiosClient.get('/notifications', { params }),
  send: (payload) => axiosClient.post('/notifications', payload),
  markAsRead: (id) => axiosClient.patch(`/notifications/${id}/read`),
}

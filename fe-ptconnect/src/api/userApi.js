import axiosClient from './axiosClient'

export const userApi = {
  getByRole: (roles) => axiosClient.get('/users', { params: { role: roles.join(',') } }),
}

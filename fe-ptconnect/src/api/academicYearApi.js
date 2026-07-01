import axiosClient from './axiosClient'

export const academicYearApi = {
  getAll: (params) => axiosClient.get('/academic-years', { params }),
}

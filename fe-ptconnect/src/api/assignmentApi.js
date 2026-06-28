import axiosClient from './axiosClient'

export const assignmentApi = {
  getAll: () => axiosClient.get('/assignments'),
  create: (payload) =>
    axiosClient.post('/assignments', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      loadingLabel: 'Đang tải bài tập lên',
    }),
  submit: (assignmentId, payload) =>
    axiosClient.post(`/assignments/${assignmentId}/submissions`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      loadingLabel: 'Đang nộp bài',
    }),
  downloadAttachment: (assignmentId) =>
    axiosClient.get(`/assignments/${assignmentId}/attachment`, {
      responseType: 'blob',
      showLoading: false,
    }),
  downloadSubmission: (submissionId) =>
    axiosClient.get(`/assignment-submissions/${submissionId}/download`, {
      responseType: 'blob',
      showLoading: false,
    }),
}

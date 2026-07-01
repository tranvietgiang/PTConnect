<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => 'required|exists:classrooms,id',
            'attendance_date' => 'required|date',
            'lesson_number' => 'required|integer|min:1',
            'session_name' => 'nullable|string|max:255',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:student_profiles,id',
            'records.*.status' => 'required|string|in:present,absent,late,excused',
            'records.*.late_minutes' => 'nullable|integer|min:0',
            'records.*.note' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'classroom_id.required' => 'Vui lòng chọn lớp.',
            'records.required' => 'Danh sách điểm danh không được trống.',
            'records.*.status.in' => 'Trạng thái điểm danh không hợp lệ.',
        ];
    }
}

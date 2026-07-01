<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'teacher_id' => 'required|exists:users,id',
            'assistant_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|in:10,11,12',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'total_lessons' => 'nullable|integer|min:1',
            'study_days' => 'nullable|array',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'max_students' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:upcoming,active,completed,cancelled',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên lớp.',
            'course_id.required' => 'Vui lòng chọn khóa học.',
            'academic_year_id.required' => 'Vui lòng chọn năm học.',
            'teacher_id.required' => 'Vui lòng chọn giáo viên.',
            'grade_level.required' => 'Vui lòng chọn khối lớp.',
            'grade_level.in' => 'Khối lớp chỉ được là 10, 11 hoặc 12.',
        ];
    }
}

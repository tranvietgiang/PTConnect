<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'student_email' => 'required|email|max:255|unique:users,email',
            'parent_email' => 'required|email|max:255',
            'high_school_name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'cccd' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'student_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'parent_full_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'parent_relation' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên học sinh.',
            'student_email.required' => 'Vui lòng nhập email học sinh.',
            'student_email.email' => 'Email học sinh không đúng định dạng.',
            'student_email.unique' => 'Email học sinh đã tồn tại trong hệ thống.',
            'parent_email.required' => 'Vui lòng nhập email phụ huynh.',
            'parent_email.email' => 'Email phụ huynh không đúng định dạng.',
            'high_school_name.required' => 'Vui lòng nhập tên trường.',
            'classroom_id.required' => 'Vui lòng chọn lớp.',
            'classroom_id.exists' => 'Lớp không tồn tại.',
        ];
    }
}

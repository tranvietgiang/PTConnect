<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'classroom_id' => 'nullable|exists:classrooms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file Excel hoặc CSV.',
            'file.mimes' => 'File phải có định dạng xlsx, xls hoặc csv.',
            'file.max' => 'File không được vượt quá 10MB.',
            'classroom_id.exists' => 'Lớp không tồn tại.',
        ];
    }
}

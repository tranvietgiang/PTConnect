<?php

namespace App\Http\Requests\Score;

use Illuminate\Foundation\Http\FormRequest;

class StoreScoreColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => 'required|exists:classrooms,id',
            'name' => 'required|string|max:255',
            'max_score' => 'nullable|numeric|min:0|max:999.99',
            'weight' => 'nullable|numeric|min:0|max:999.99',
            'test_date' => 'nullable|date',
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên cột điểm.',
            'classroom_id.required' => 'Vui lòng chọn lớp.',
        ];
    }
}

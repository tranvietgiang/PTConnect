<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'required_without:username', 'string'],
            'username' => ['nullable', 'required_without:email', 'string'],
            'password' => ['required', 'string'],
            'remember_me' => ['sometimes', 'boolean'],
        ];
    }
}

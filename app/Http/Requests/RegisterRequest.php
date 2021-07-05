<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return !auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'required|string|regex:/^[a-zA-Z0-9_]+$/ui|min:4|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'introducer' => 'nullable|string|min:4',
            'role' => 'required|string',
        ];
    }
}

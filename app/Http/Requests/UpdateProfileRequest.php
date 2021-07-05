<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'position' => 'required|string|min:3',
            'gender' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|min:1',
            'bio' => 'nullable|string',
            'marital_status' => 'nullable|boolean',
            'birth_date' => 'nullable',
            'languages_list' => 'nullable|array',
        ];
    }
}

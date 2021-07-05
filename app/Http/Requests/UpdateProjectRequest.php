<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
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
            'title' => 'required|string|min:3',
            'description' => 'required|string|min:3',
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0',
            'new_attachments' => 'nullable|array',
            'new_properties' => 'nullable|array',
            'new_skills' => 'nullable|array',
        ];
    }
}

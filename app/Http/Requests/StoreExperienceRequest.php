<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExperienceRequest extends FormRequest
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
            'company' => 'required|string|min:3',
            'description' => 'nullable|string|min:3',
            'up_to_now' => 'required|boolean',
            'from_date' => 'required',
            'to_date' => 'nullable',
        ];
    }
}

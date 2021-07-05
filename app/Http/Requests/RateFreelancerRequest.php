<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateFreelancerRequest extends FormRequest
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
            'rate' => 'required|numeric|min:0|max:5',
            'description' => 'nullable|string|min:3',
        ];
    }
}

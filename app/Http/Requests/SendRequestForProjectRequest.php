<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendRequestForProjectRequest extends FormRequest
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
            'title' => 'nullable|string|min:3',
            'price' => 'required|numeric|min:0',
            'delivery_date' => 'required|numeric|min:1',
            'description' => 'required|string|min:3',
            'is_distinguished' => 'required|boolean',
            'is_sponsored' => 'required|boolean',
            'project_id' => 'required|exists:projects,id',
            'new_secure_payments' => 'nullable|array'
        ];
    }
}

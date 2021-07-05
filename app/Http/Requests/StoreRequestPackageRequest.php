<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestPackageRequest extends FormRequest
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
            'color' => 'nullable|string|min:3',
            'title' => 'required|string|min:3',
            'type' => 'nullable|string|min:3',
            'description' => 'nullable|string|min:3',
            'price' => 'required|numeric|min:0',
            'number' => 'required|numeric|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
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
            'name' => 'required|string|min:3|unique:project_properties',
            'description' => 'nullable|string|min:3',
            'color' => 'required|string|min:3',
            'bg_color' => 'required|string|min:3',
            'price' => 'required|numeric|min:0',
            'icon_id' => 'nullable|exists:uploads,id'
        ];
    }
}

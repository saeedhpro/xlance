<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
            'caption' => 'required|string|min:3,max:250',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'image_id' => 'required|exists:uploads,id',
        ];
    }
}

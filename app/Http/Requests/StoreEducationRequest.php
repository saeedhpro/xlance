<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationRequest extends FormRequest
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
            'degree' => 'required|string|min:3',
            'school_name' => 'required|string|min:3',
            'up_to_now' => 'required|boolean',
            'from_date' => 'required',
            'to_date' => 'nullable',
        ];
    }
}

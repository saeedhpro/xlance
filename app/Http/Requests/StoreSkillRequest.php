<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
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
            'name' => 'required|string|min:1|unique:skills',
            'category_id' => 'required|exists:categories,id',
            'color' => 'nullable|string|min:7|max:7',
            'status' => 'nullable|boolean',
            'keywords_list' => 'nullable|array',
        ];
    }
}

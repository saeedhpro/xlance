<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
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
            'body' => 'required|string|min:3',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'new_image_id' => 'nullable|exists:uploads,id'
        ];
    }
}

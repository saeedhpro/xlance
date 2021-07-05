<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'conversation_id' => 'required|exists:conversations,id',
            'to_id' => 'required|exists:users,id',
            'upload_id' => 'nullable',
            'body' => 'required|string|min:1',
            'type' => 'required|string|in:file,text'
        ];
    }
}

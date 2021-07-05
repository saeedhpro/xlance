<?php

namespace App\Http\Requests;

use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSkillRequest extends FormRequest
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
        $skill = Skill::find($this->route('skill'));
        return [
            'name' => 'required|string|min:1|unique:skills,name,'.$skill->id,
            'category_id' => 'required|exists:categories,id',
            'color' => 'nullable|string|min:7|max:7',
            'status' => 'nullable|boolean',
            'keywords_list' => 'nullable|array',
        ];
    }
}

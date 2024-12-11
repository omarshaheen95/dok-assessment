<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class TermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'name' => 'required',
            'year_id' => 'required',
            'level_id' => 'required',
            'round' => 'required|in:september,february,may',
            'duration' => 'required',
            'demo' => 'nullable',
        ];
        return $rules;
    }
}

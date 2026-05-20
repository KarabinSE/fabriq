<?php

namespace Karabin\Fabriq\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockTypeRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|required|max:255',
            'component_name' => 'sometimes|required|max:255',
            'base_64_svg' => 'sometimes|string|nullable',
            'has_children' => 'sometimes|boolean',
            'options' => 'sometimes|array',
            'file' => 'sometimes|file|nullable',
        ];
    }
}

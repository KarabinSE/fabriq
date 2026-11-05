<?php

namespace Karabin\Fabriq\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
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
            'item.type' => 'required',
            'item.page_id' => 'required_if:item.type,internal|nullable',
            'has_custom_title' => 'required|boolean',
            'content' => 'array',
            'content.title' => 'required_if:item.type,external,file|max:255',
            'content.external_url' => 'required_if:item.type,external|max:255',
        ];
    }
}

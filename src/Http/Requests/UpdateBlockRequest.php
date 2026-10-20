<?php

namespace Karabin\Fabriq\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'blocks' => 'present|array',
            'blocks.*.locale' => 'required',
            'blocks.*.name' => 'required|max:255',
            'blocks.*.block_type_id' => 'required|integer|exists:block_types,id',
        ];
    }
}

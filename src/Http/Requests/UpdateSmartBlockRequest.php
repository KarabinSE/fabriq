<?php

namespace Karabin\Fabriq\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSmartBlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'max:255'],
            'localizedContent' => ['required', 'array'],
        ];
    }
}

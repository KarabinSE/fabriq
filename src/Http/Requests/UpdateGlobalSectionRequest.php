<?php

namespace Karabin\Fabriq\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\GlobalSection;

class UpdateGlobalSectionRequest extends FormRequest
{
    private ?GlobalSection $section = null;

    public function section(): GlobalSection
    {
        return $this->section ??= GlobalSection::with('template.fields')
            ->where('handle', $this->route('handle'))
            ->firstOrFail();
    }

    public function rules(): array
    {
        $rules = [
            'locale' => ['required', 'string', Rule::exists('i18n_locales', 'iso_code')->where('enabled', true)],
            'content' => ['required', 'array'],
        ];

        foreach ($this->section()->template->fields as $field) {
            $rules['content.'.$field->key] = $field->type === 'image'
                ? ['nullable', 'integer', Rule::exists(Fabriq::getModelClass('image')->getTable(), 'id')]
                : ['nullable', 'string'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $content = $this->input('content');
            if (! is_array($content)) {
                return;
            }

            $fields = $this->section()->template->fields->keyBy('key');
            foreach (array_keys($content) as $key) {
                if (! $fields->has($key)) {
                    $validator->errors()->add('content.'.$key, 'This field is not defined for this global section.');
                }
            }
        });
    }
}

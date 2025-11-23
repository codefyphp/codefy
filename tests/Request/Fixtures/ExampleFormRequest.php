<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Request\Fixtures;

use Codefy\Framework\Http\Request\FormRequest;

class ExampleFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return [
            'id' => 'required|string',
        ];
    }
}

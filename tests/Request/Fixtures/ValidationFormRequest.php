<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Request\Fixtures;

use Codefy\Framework\Http\Middleware\Request\FormRequest;
use Psr\Http\Message\ServerRequestInterface;

class ValidationFormRequest extends FormRequest
{
    public function authorize(ServerRequestInterface $request): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return [
                'id' => 'required|integer',
        ];
    }
}

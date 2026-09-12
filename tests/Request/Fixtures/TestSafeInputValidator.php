<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Request\Fixtures;

use Codefy\Framework\Validation\HttpInputValidator;

class TestSafeInputValidator extends HttpInputValidator
{
    protected function rules(): array
    {
        return ['id' => 'required|string'];
    }
}

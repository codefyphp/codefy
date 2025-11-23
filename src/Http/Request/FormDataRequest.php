<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Request;

abstract class FormDataRequest extends FormRequest
{
    protected function passedValidation(): void
    {
        foreach ($this->validated() as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}

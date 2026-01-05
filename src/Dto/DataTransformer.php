<?php

declare(strict_types=1);

namespace Codefy\Framework\Dto;

use Codefy\Framework\Http\Request\DataTransformerRequest;
use Codefy\Framework\Validation\DataValidator;

interface DataTransformer
{
    public static function fromData(DataValidator|DataTransformerRequest $data): self;
}

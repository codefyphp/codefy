<?php

declare(strict_types=1);

namespace Codefy\Framework\Dto;

use Codefy\Framework\Validation\DataValidator;

interface DataTransformer
{
    public static function fromValidatedData(DataValidator $data): self;
}

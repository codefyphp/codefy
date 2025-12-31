<?php

declare(strict_types=1);

namespace Codefy\Framework\Dto;

use Codefy\Framework\Http\Request\DataTransformerRequest;

interface DataTransformer
{
    public static function fromRequest(DataTransformerRequest $request): self;
}

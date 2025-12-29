<?php

declare(strict_types=1);

namespace Codefy\Framework\Dto\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UseDto
{
    /**
     * Create a new UseDto attribute instance.
     *
     * @param class-string $dtoClass The fully qualified DTO class name.
     */
    public function __construct(
        public string $dtoClass
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Dto\Trait;

use Codefy\Framework\Dto\Attribute\UseDto;

use function get_object_vars;
use function sprintf;

// @phpstan-ignore trait.unused
trait DtoAware
{
    /**
     * Convert validated data to DTO.
     *
     * This method creates a DTO instance using only validated data from Input Validator or Form Request.
     *
     * @return object The corresponding DTO instance.
     * @throws \RuntimeException If DTO class is not found or doesn't have fromData method.
     */
    public function toDto(): object
    {
        $dtoClass = $this->getDtoClass();

        if (! class_exists(class: $dtoClass)) {
            throw new \RuntimeException(
                message: sprintf("DTO class [%s] not found.", $dtoClass)
            );
        }

        if (! method_exists(object_or_class: $dtoClass, method: 'fromValidatedData')) {
            throw new \RuntimeException(
                message: sprintf("DTO class [%s] must have a 'fromValidatedData' method.", $dtoClass)
            );
        }

        return $dtoClass::fromValidatedData($this);
    }

    /**
     * Get the DTO class name for this Input Validator or Form Request.
     *
     * @return string The fully qualified DTO class name
     */
    public function getDtoClass(): string
    {
        $reflection = new \ReflectionClass(objectOrClass: static::class);
        $attributes = $reflection->getAttributes(name: UseDto::class);

        if ($attributes !== []) {
            $attribute = $attributes[0]->newInstance();

            return $attribute->dtoClass;
        }

        return '';
    }

    /**
     * Convert DTO object data to array.
     *
     * @return array<mixed>
     */
    public function toDtoArray(): array
    {
        return get_object_vars(object: $this->toDto());
    }
}

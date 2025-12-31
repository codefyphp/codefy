<?php

declare(strict_types=1);

namespace Codefy\Framework\Dto\Trait;

use Codefy\Framework\Dto\Attribute\UseDto;
use ReflectionClass;
use RuntimeException;

use function get_object_vars;
use function sprintf;

trait DtoAware
{
    /**
     * Convert validated data to DTO.
     *
     * This method creates a DTO instance using only validated data from the Form Request.
     *
     * @return object The corresponding DTO instance.
     * @throws RuntimeException If DTO class is not found or doesn't have fromRequest method.
     */
    public function toDto(): object
    {
        $dtoClass = $this->getDtoClass();

        if (! class_exists(class: $dtoClass)) {
            throw new RuntimeException(
                message: sprintf("DTO class [%s] not found.", $dtoClass)
            );
        }

        if (! method_exists(object_or_class: $dtoClass, method: 'fromRequest')) {
            throw new RuntimeException(
                message: sprintf("DTO class [%s] must have a 'fromRequest' method.", $dtoClass)
            );
        }

        return $dtoClass::fromRequest($this);
    }

    /**
     * Get the DTO class name for this Form Request.
     *
     * @return string The fully qualified DTO class name
     */
    public function getDtoClass(): string
    {
        $reflection = new ReflectionClass(objectOrClass: static::class);
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
     * @return array
     */
    public function toDtoArray(): array
    {
        return get_object_vars(object: $this->toDto());
    }
}

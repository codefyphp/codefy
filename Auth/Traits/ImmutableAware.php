<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Traits;

use function get_class;
use function property_exists;
use function sprintf;

trait ImmutableAware
{
    /**
     * @throws BadPropertyCallException
     */
    private function withProperty(string $property, mixed $value): static
    {
        if (!property_exists($this, $property)) {
            throw new BadPropertyCallException(
                sprintf(
                    'Property %s does not exist in %s.',
                    $property,
                    get_class($this)
                )
            );
        }

        if ((isset($this->{$property}) && $this->{$property} === $value) ||
            (!isset($this->{$property}) && $value === null)
        ) {
            return $this;
        }

        $clone = clone $this;
        $clone->{$property} = $value;

        return $clone;
    }
}

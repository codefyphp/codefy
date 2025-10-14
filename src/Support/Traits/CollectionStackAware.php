<?php

declare(strict_types=1);

namespace Codefy\Framework\Support\Traits;

use Qubus\Support\Collection\ArrayCollection as Collection;

use function array_merge;
use function in_array;
use function is_int;

trait CollectionStackAware
{
    protected array $collection = [];

    /**
     * Merge the given collection into another collection.
     *
     * @param array $collection
     * @return static
     */
    public function merge(array $collection): static
    {
        $this->collection = array_merge($this->collection, $collection);

        return new static($this->collection);
    }

    /**
     * Replace the given collection with other collections.
     *
     * @param  array  $replacements
     * @return static
     */
    public function replace(array $replacements): static
    {
        $current = new Collection($this->collection);

        foreach ($replacements as $from => $to) {
            $key = $current->search($from);

            $current = is_int($key) ? $current->replace([$key => $to]) : $current;
        }

        return new static($current->values()->toArray());
    }

    /**
     * Disable the given collection.
     *
     * @param array $collection
     * @return static
     */
    public function except(array $collection): static
    {
        return new static(
            new Collection($this->collection)
                ->reject(fn ($p) => in_array($p, $collection))
                ->values()
                ->toArray()
        );
    }

    /**
     * Convert the collection to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection;
    }
}

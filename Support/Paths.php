<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Qubus\Exception\Data\TypeException;

/**
 * @property-read string path
 * @property-read string base
 * @property-read string bootstrap
 * @property-read string config
 * @property-read string database
 * @property-read string locale
 * @property-read string public
 * @property-read string storage
 * @property-read string resource
 * @property-read string view
 * @property-read string vendor
 */
final class Paths
{
    private array $paths = [];

    /**
     * @throws TypeException
     */
    public function __construct(array $paths)
    {
        if (! isset(
            $paths['path'],
            $paths['base'],
            $paths['bootstrap'],
            $paths['config'],
            $paths['database'],
            $paths['locale'],
            $paths['public'],
            $paths['storage'],
            $paths['resource'],
            $paths['view']
        )
        ) {
            throw new TypeException(
                message: 'Paths array requires the following keys: path, base, bootstrap, config, database,
                locale, public, storage, resource, and view'
            );
        }

        $this->paths = array_map(callback: function ($path) {
            return rtrim(string: $path, characters: '\/');
        }, array: $paths);

        // Assume a standard Composer directory structure unless specified
        $this->paths['vendor'] = $this->vendor ?? $this->base.'/vendor';
    }

    public function __get(mixed $name): ?string
    {
        return $this->paths[$name] ?? null;
    }
}

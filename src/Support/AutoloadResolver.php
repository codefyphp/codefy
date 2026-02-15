<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Proxy\Codefy;

use function Codefy\Framework\Helpers\base_path;

class AutoloadResolver
{
    /**
     * @return array<array-key, mixed>
     */
    public static function getPsr4Mappings(): array
    {
        $composer = json_decode(
            file_get_contents(base_path(path: 'composer.json')),
            true
        );

        if (!isset($composer['autoload']['psr-4'])) {
            return [];
        }

        $mappings = $composer['autoload']['psr-4'];

        // Normalize paths
        foreach ($mappings as $ns => &$path) {
            $path = rtrim(base_path($path), Codefy::$PHP::DS);
        }

        return $mappings;
    }

    /**
     * Resolves a namespace to its PSR-4 path.
     */
    public static function resolveNamespaceToPath(string $namespace): ?string
    {
        $mappings = self::getPsr4Mappings();

        foreach ($mappings as $prefix => $directory) {
            if (str_starts_with($namespace, $prefix)) {
                $sub = substr($namespace, strlen($prefix));
                $sub = str_replace('\\', Codefy::$PHP::DS, $sub);
                return rtrim($directory . Codefy::$PHP::DS . $sub, Codefy::$PHP::DS);
            }
        }

        return null;
    }
}

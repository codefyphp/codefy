<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Proxy\Codefy;

use function Codefy\Framework\Helpers\base_path;

class AutoloadResolver
{
    public static function getPsr4Mappings(): array
    {
        $map = require base_path(path: 'vendor/composer/autoload_psr4.php');

        // normalize paths (Composer returns arrays of paths)
        return array_map(fn($paths) => rtrim($paths[0], Codefy::$PHP::DS), $map);
    }

    public static function resolveNamespaceToPath(string $namespace): ?string
    {
        $mappings = self::getPsr4Mappings();

        foreach ($mappings as $prefix => $directory) {
            if (str_starts_with($namespace, $prefix)) {
                $sub = str_replace('\\', Codefy::$PHP::DS, substr($namespace, strlen($prefix)));
                return $directory . Codefy::$PHP::DS . $sub;
            }
        }

        return null;
    }
}

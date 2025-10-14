<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Application;
use RuntimeException;

use function sprintf;

final class Assets extends \Qubus\Support\Assets
{
    public function __construct(protected Application $codefy, array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Get the instance of the assets manager for a given group.
     *
     * @param string $group
     *
     * @return Assets
     *
     */
    public function group(string $group = 'default'): self
    {
        $binding = "assets.group.$group";

        $assets = $this->codefy->make($binding);

        if (!$assets instanceof Assets) {
            throw new RuntimeException(
                message: sprintf("Assets group '%s' not found in the config file", $group)
            );
        }

        return $assets;
    }
}

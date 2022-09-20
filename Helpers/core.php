<?php

declare(strict_types=1);

namespace Codefy\Foundation\Helpers;

use Codefy\Foundation\Application;
use Qubus\Config\Collection;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;

use function file_exists;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function rtrim;

/**
 * Get the available container instance.
 *
 * @param  string|null  $name
 * @param  array  $args
 * @return mixed
 */
function app(?string $name = null, array $args = []): mixed
{
    $app = get_fresh_bootstrap();

    if (is_null__($name)) {
        return $app->getContainer();
    }
    return $app->getContainer()->make($name, $args);
}

/**
 * Get the available config instance.
 *
 * @param string $key
 * @param array|bool $set
 * @return ConfigContainer
 */
function config(string $key, array|bool $set = false)
{
    if (!is_false__($set)) {
        app(Collection::class)->setConfigKey($key, $set);
        return app(Collection::class)->getConfigKey($key);
    }

    return app(Collection::class)->getConfigKey($key);
}

/**
 * Retrieve a fresh instance of the bootstrap.
 *
 * @return mixed
 */
function get_fresh_bootstrap(): mixed
{
    if (file_exists($file = __DIR__ . '/../../../../../bootstrap/app.php')) {
        return require($file);
    } elseif (file_exists($file = __DIR__ . '/../../../../bootstrap/app.php')) {
        return require($file);
    } elseif (file_exists($file = __DIR__ . '/../../bootstrap/app.php')) {
        return require($file);
    } elseif (file_exists(
        $file = rtrim(string: (string) env('APP_BASE_PATH'), characters: '/') . '/bootstrap.app.php'
    )
    ) {
        return require($file);
    } else {
        return require(__DIR__ . '/../bootstrap/app.php');
    }
}

/**
 * Gets the value of an environment variable.
 *
 * @param string $key
 * @param mixed|null $default
 * @return mixed|null
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

/**
 * Database Instance.
 *
 * @return OrmBuilder
 * @throws Exception
 */
function db(): OrmBuilder
{
    return Application::$APP->getDB();
}

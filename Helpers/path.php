<?php

declare(strict_types=1);

namespace Codefy\Framework\Helpers;

use Codefy\Framework\Application;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use function implode;
use function ltrim;
use function Qubus\Security\Helpers\__observer;
use function str_replace;

/**
 * Get the path to the application base directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function base_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->base, $path);
}

/**
 * Get the path to the application "src" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function src_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->base, $path);
}

/**
 * Get the path to the application "bootstrap" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function bootstrap_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->bootstrap, $path);
}

/**
 * Get the path to the application "config" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function config_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->config, $path);
}

/**
 * Get the path to the application "database" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function database_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->database, $path);
}

/**
 * Get the path to the application "locale" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function locale_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->locale, $path);
}

/**
 * Get the path to the application "public" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function public_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->public, $path);
}

/**
 * Get the path to the application "storage" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function storage_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->storage, $path);
}

/**
 * Get the path to the application "resource" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function resource_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->resource, $path);
}

/**
 * Get the path to the application "view" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function view_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->view, $path);
}

/**
 * Get the path to the application "vendor" directory.
 *
 * @param string|null $path
 * @return string
 * @throws TypeException
 */
function vendor_path(?string $path = null): string
{
    return join_paths(app(name: 'dir.path')->vendor, $path);
}

/**
 * @throws Exception
 */
function router_basepath(string $path): string
{
    $fullPath = str_replace(search: $_SERVER['DOCUMENT_ROOT'], replace: '', subject: $path);

    $filteredPath = __observer()->filter->applyFilter('router.basepath', $fullPath);

    return ltrim(string: $filteredPath, characters: '/') . '/';
}

/**
 * Join the given paths together.
 *
 * @param  string|null  $basePath
 * @param  string  ...$paths
 * @return string
 */
function join_paths(?string $basePath = null, ...$paths): string
{
    foreach ($paths as $index => $path) {
        if (empty($path) && $path !== '0') {
            unset($paths[$index]);
        } else {
            $paths[$index] = Application::DS . ltrim(string: $path, characters: Application::DS);
        }
    }

    return $basePath . implode(separator: '', array: $paths);
}

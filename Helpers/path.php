<?php

declare(strict_types=1);

namespace Codefy\Foundation\Helpers;

use Codefy\Foundation\Application;

use function ltrim;
use function Qubus\Security\Helpers\__observer;
use function Qubus\Support\Helpers\is_null__;
use function str_replace;

/**
 * Get the path to the application base directory.
 *
 * @param string|null $path
 * @return string
 */
function base_path(?string $path = null): string
{
    return app(name: 'dir.path')->base.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "src" directory.
 *
 * @param string|null $path
 * @return string
 */
function src_path(?string $path = null): string
{
    return app(name: 'dir.path')->path.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "bootstrap" directory.
 *
 * @param string|null $path
 * @return string
 */
function bootstrap_path(?string $path = null): string
{
    return app(name: 'dir.path')->bootstrap.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "config" directory.
 *
 * @param string|null $path
 * @return string
 */
function config_path(?string $path = null): string
{
    return app(name: 'dir.path')->config.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "locale" directory.
 *
 * @param string|null $path
 * @return string
 */
function locale_path(?string $path = null): string
{
    return app(name: 'dir.path')->locale.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "public" directory.
 *
 * @param string|null $path
 * @return string
 */
function public_path(?string $path = null): string
{
    return app(name: 'dir.path')->public.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "storage" directory.
 *
 * @param string|null $path
 * @return string
 */
function storage_path(?string $path = null): string
{
    return app(name: 'dir.path')->storage.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "resource" directory.
 *
 * @param string|null $path
 * @return string
 */
function resource_path(?string $path = null): string
{
    return app(name: 'dir.path')->resource.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "view" directory.
 *
 * @param string|null $path
 * @return string
 */
function view_path(?string $path = null): string
{
    return app(name: 'dir.path')->view.(!is_null__(var: $path) ? Application::DS.$path : '');
}

/**
 * Get the path to the application "vendor" directory.
 *
 * @param string|null $path
 * @return string
 */
function vendor_path(?string $path = null): string
{
    return app(name: 'dir.path')->vendor.(!is_null__(var: $path) ? Application::DS.$path : '');
}

function router_basepath(string $path): string
{
    $fullPath = str_replace(search: $_SERVER['DOCUMENT_ROOT'], replace: '', subject: $path);

    $filteredPath = __observer()->filter->applyFilter('router.basepath', $fullPath);

    return ltrim(string: $filteredPath, characters: '/') . '/';
}

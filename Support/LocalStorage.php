<?php

declare(strict_types=1);

namespace Codefy\Foundation\Support;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Qubus\Config\ConfigContainer;
use Qubus\FileSystem\FileSystem;

use function Codefy\Foundation\Helpers\config;

use const LOCK_EX;

final class LocalStorage
{
    public static function disk(?string $name = null): FileSystem
    {
        $name = $name ?? 'local';

        $config = self::getConfigForDriverName($name);

        return self::createInstanceOfLocalDriver($name, $config);
    }

    private static function getConfigForDriverName(string $name): array|ConfigContainer
    {
        return config(key: "filesystem.disks.{$name}") ?? [];
    }

    public static function createInstanceOfLocalDriver(string $name, array $configArray): FileSystem
    {
        $visibility = PortableVisibilityConverter::fromArray(
            self::setVisibilityConverterByDiskName(name: $name)
        );

        $links = ($configArray['links'] ?? null) === 'skip'
            ? LocalFilesystemAdapter::SKIP_LINKS
            : LocalFilesystemAdapter::DISALLOW_LINKS;

        $adapter = new LocalFilesystemAdapter(
            location: $configArray['root'],
            visibility: $visibility,
            writeFlags: $configArray['lock'] ?? LOCK_EX,
            linkHandling: $links
        );

        return new FileSystem(adapter: $adapter, configArray: $configArray);
    }

    private static function setVisibilityConverterByDiskName(string $name): array
    {
        return [
            'file' => [
                'public'  => self::getConfigForDriverName(name: $name)['visibility']['file']['public'],
                'private' => self::getConfigForDriverName(name: $name)['visibility']['file']['private'],
            ],
            'dir'  => [
                'public'  => self::getConfigForDriverName(name: $name)['visibility']['dir']['public'],
                'private' => self::getConfigForDriverName(name: $name)['visibility']['dir']['private'],
            ],
        ];
    }
}
<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use League\Flysystem\FilesystemException;
use Qubus\FileSystem\FileSystem;
use Qubus\Injector\ServiceProvider\Bootable;
use Qubus\Injector\ServiceProvider\Serviceable;
use Symfony\Component\Console\Input\InputOption;

use function basename;
use function fclose;
use function file_get_contents;
use function fopen;
use function is_dir;
use function is_resource;
use function pathinfo;
use function rtrim;
use function str_replace;
use function strlen;
use function substr;

use const PATHINFO_DIRNAME;

class VendorPublishCommand extends ConsoleCommand
{
    protected string $name = 'vendor:publish';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(
                name: 'provider',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'ServiceProvider class'
            )
            ->addOption(
                name: 'tag',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Publish group (config, migrations, etc.).'
            )
            ->addOption(
                name: 'force',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite existing files.'
            )
            ->addOption(
                name: 'list',
                shortcut: 'l',
                mode: InputOption::VALUE_NONE,
                description: 'List publishable providers and tags.'
            )
            ->setDescription(description: 'Publish any publishable assets from vendor packages')
            ->setHelp(
                help: <<<EOT
The <info>vendor:publish</info> publishes assets from vendor packages.
<info>php codex vendor:publish</info>
EOT
            );
    }

    /**
     * @throws FilesystemException
     */
    public function handle(): int
    {
        // If user wants to list publishable tags instead of publishing
        if ($this->input->getOption('list')) {
            return $this->listPublishables();
        }

        // If user wants to publish from a specific provider
        if ($this->input->getOption('provider')) {
            return $this->publishDefinedProvider();
        }

        $tag      = $this->input->getOption('tag');
        $force    = $this->input->getOption('force');

        /** @var array<Serviceable|Bootable, bool> $registeredProviders */
        $registeredProviders = $this->codefy->getRegisteredProviders();

        /**
         * @var Serviceable|Bootable $instance
         */
        foreach ($registeredProviders as $instance => $value) {

            $paths = $instance->pathsToPublish($tag);

            foreach ($paths as $from => $type) {
                $destination = $this->resolveDestination($from, $type);

                if (is_dir($from)) {
                    $this->copyDirectory($from, $destination, $force);
                } else {
                    $this->copyFile($from, $destination, $force);
                }
            }
        }

        return self::SUCCESS;
    }

    private function resolveDestination(string $from, string $type): string
    {
        // Normalize trailing separators
        $from = rtrim($from, $this->codefy::DS);

        return match ($type) {
            'config'     => is_dir($from) ? 'config' : 'config/' . basename($from),
            'migrations' => is_dir($from) ? 'database/migrations' : 'database/migrations/' . basename($from),
            default      => $type, // allow explicit path fallback
        };
    }

    /**
     * @throws FilesystemException
     */
    private function copyFile(string $from, string $to, bool $force): void
    {
        /** @var FileSystem $filesystem */
        $filesystem = $this->codefy->make(name: 'filesystem.default');

        // Ensure parent directory exists in Flysystem
        $dir = pathinfo($to, PATHINFO_DIRNAME);
        if ($dir !== '' && ! $filesystem->directoryExists($dir)) {
            $filesystem->createDirectory($dir);
        }

        if (! $force && $filesystem->fileExists($to)) {
            $this->output->writeln("<comment>Skipped {$to}, file exists.</comment>");
            return;
        }

        // Prefer streaming for large files, fallback to write()
        $stream = fopen($from, 'rb');
        if ($stream !== false) {
            $filesystem->writeStream($to, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } else {
            $contents = file_get_contents($from);
            $filesystem->write($to, $contents === false ? '' : $contents);
        }

        $this->output->writeln("<info>Published:</info> {$from} -> {$to}");
    }

    /**
     * @throws FilesystemException
     */
    private function copyDirectory(string $from, string $to, bool $force): void
    {
        $from = rtrim($from, $this->codefy::DS);
        $to   = rtrim($to, $this->codefy::DS);

        $dirIterator = new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS);
        $iterator    = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iterator as $fileInfo) {
            /** @var \SplFileInfo $fileInfo */
            if ($fileInfo->isDir()) {
                continue;
            }

            // Compute relative path robustly (works on Windows and *nix)
            $absolutePath = $fileInfo->getPathname();
            $relative = substr($absolutePath, strlen($from) + 1);
            $relative = str_replace(['\\', '/'], $this->codefy::DS, $relative);

            $target = $to . $this->codefy::DS . $relative;

            $this->copyFile($absolutePath, $target, $force);
        }
    }

    private function listPublishables(): int
    {
        /** @var array<Serviceable|Bootable, bool> $providers */
        $providers = $this->codefy->getRegisteredProviders();

        $this->output->writeln("<info>Available publishable resources:</info>");

        /**
         * @var Serviceable|Bootable $instance
         */
        foreach ($providers as $instance => $value) {

            foreach (['config', 'migrations'] as $tag) {
                $paths = $instance->pathsToPublish($tag);
                if (!empty($paths)) {
                    $this->output->writeln("    - tag: <info>{$tag}</info>");
                    foreach ($paths as $from => $type) {
                        $this->output->writeln("       {$from} -> {$this->resolveDestination($from, $type)}");
                    }
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * @throws FilesystemException
     */
    private function publishDefinedProvider(): int
    {
        /** @var array<Serviceable|Bootable, bool> $registeredProviders */
        $registeredProviders = $this->codefy->getRegisteredProviders();

        $provider = $this->input->getOption('provider');
        $tag      = $this->input->getOption('tag');
        $force    = $this->input->getOption('force');

        $providers = $this->findKeysLike($provider, $registeredProviders);

        /**
         * @var Serviceable|Bootable $p
         */
        foreach ($providers as $int => $p) {

            $paths = $p->pathsToPublish($tag);

            foreach ($paths as $from => $type) {
                $destination = $this->resolveDestination($from, $type);

                if (is_dir($from)) {
                    $this->copyDirectory($from, $destination, $force);
                } else {
                    $this->copyFile($from, $destination, $force);
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Search array keys for a substring and return all matching keys.
     *
     * @param string                             $needle
     * @param array<Serviceable|Bootable, bool>  $haystack
     * @param bool                               $caseSensitive
     *
     * @return list<string> List of matching keys (empty if none found).
     */
    protected function findKeysLike(string $needle, array $haystack, bool $caseSensitive = true): array
    {
        $matches = [];

        foreach ($haystack as $key => $_) {
            if (!is_string($key)) {
                continue;
            }

            $hay = $caseSensitive ? $key : strtolower($key);
            $needleCmp = $caseSensitive ? $needle : strtolower($needle);

            if (str_contains($hay, $needleCmp)) {
                $matches[] = $key;
            }
        }

        return $matches;
    }
}

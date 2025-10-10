<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use FilesystemIterator;
use League\Flysystem\FilesystemException;
use Qubus\FileSystem\FileSystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputOption;

use function basename;
use function fclose;
use function file_get_contents;
use function fopen;
use function is_dir;
use function is_resource;
use function method_exists;
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
                name: 'tag',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Publish group (config, migrations, etc.).'
            )
            ->addOption(
                name: 'force',
                shortcut: '--f',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite existing files.'
            )
            ->addOption(
                name: 'list',
                shortcut: '--l',
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

    public function handle(): int
    {
        // If user wants to list publishable tags instead of publishing
        if ($this->input->getOption('list')) {
            return $this->listPublishables();
        }

        $tag      = $this->input->getOption('tag');
        $force    = $this->input->getOption('force');

        $providers = $this->codefy->getRegisteredProviders();

        foreach ($providers as $instance => $value) {

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

        return ConsoleCommand::SUCCESS;
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
        if ($stream !== false && method_exists($filesystem, 'writeStream')) {
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

        $dirIterator = new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS);
        $iterator    = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
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
        $providers = $this->codefy->getRegisteredProviders();

        $this->output->writeln("<info>Available publishable resources:</info>");

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

        return ConsoleCommand::SUCCESS;
    }
}

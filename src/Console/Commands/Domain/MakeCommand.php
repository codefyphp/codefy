<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands\Domain;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ClassGenerator;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Console\PresetRegistry;
use Codefy\Framework\Support\AutoloadResolver;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MakeCommand extends ConsoleCommand
{
    protected string $name = 'ddd:make';
    protected string $description = 'Generate a class from a preset stub.';

    public function __construct(
        protected PresetRegistry $presets,
        protected ClassGenerator $generator,
        protected Application $codefy
    ) {
        parent::__construct($codefy);
    }

    /**
     * @throws Exception
     */
    protected function handle(): int
    {
        /** --------------------------------------------------------------
         * 1. Select a preset
         * --------------------------------------------------------------*/
        $presetKeys = array_keys($this->presets->all());
        $presetLabels = array_map(
            fn($k) => $this->presets->all()[$k]['label'] ?? $k,
            $presetKeys
        );

        $selectedPresetLabel = $this->choice(
            'Select the type of class to generate:',
            $presetLabels,
        );
        $presetIndex = array_search($selectedPresetLabel, $presetLabels);
        $presetKey = $presetKeys[$presetIndex];
        $preset = $this->presets->get($presetKey);

        /** --------------------------------------------------------------
         * 2. Select a namespace
         * --------------------------------------------------------------*/
        $mappings = AutoloadResolver::getPsr4Mappings();
        $namespaces = array_keys($mappings);

        $namespace = $this->choice(
            'Select the namespace to generate the class in:',
            $namespaces
        );

        /** --------------------------------------------------------------
         * 3. Select a folder inside the namespace root
         * --------------------------------------------------------------*/
        $rootPath = $mappings[$namespace];
        $folders = $this->scanDirectories($rootPath);

        array_unshift($folders, '[root directory]');

        $selectedFolder = $this->choice(
            "Select subdirectory inside {$namespace}:",
            $folders
        );
        $directory = $selectedFolder === '[root directory]' ? '' : $selectedFolder;

        /** --------------------------------------------------------------
         * 4. Ask for class name
         * --------------------------------------------------------------*/
        $className = $this->ask('Class name (no namespace): ');

        /** --------------------------------------------------------------
         * 5. Generate!
         * --------------------------------------------------------------*/
        $created = $this->generator->generate(
            preset: $preset,
            namespace: $namespace . ($directory ? '\\' . str_replace('/', '\\', $directory) : ''),
            directory: $directory,
            className: $className
        );

        foreach ($created as $path) {
            $this->output->writeln("<info>Created:</info> {$path}");
        }

        return ConsoleCommand::SUCCESS;
    }

    private function scanDirectories(string $root): array
    {
        $dirs = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $relative = str_replace($root . $this->codefy::DS, '', $item->getPathname());
                if ($relative !== '') {
                    $dirs[] = $relative;
                }
            }
        }

        sort($dirs);
        return $dirs;
    }
}

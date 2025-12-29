<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Codefy\Framework\Application;
use Codefy\Framework\Support\AutoloadResolver;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\FileSystem\FileSystem;

final class ClassGenerator
{
    public function __construct(
        protected Application $codefy,
        protected ConfigContainer $configContainer,
        protected FileSystem $filesystem
    ) {
    }

    /**
     * Generate class files from a preset.
     *
     * @param array       $preset
     * @param string      $namespace
     * @param string      $directory
     * @param string      $className
     * @param string|null $overridePath
     * @return array
     * @throws Exception
     */
    public function generate(
        array $preset,
        string $namespace,
        string $directory,
        string $className,
        ?string $overridePath = null
    ): array {
        $created   = [];
        $stubsPath = $this->configContainer->getConfigKey(key: 'stubs.stubs_path');

        /**
         * ---------------------------------------------------------------------
         * Determine base output directory
         * ---------------------------------------------------------------------
         */
        if ($overridePath !== null) {
            // overridePath is a *full path*, we want only the directory
            $baseOutputPath = dirname($overridePath);

        } else {
            // old behavior (fallback)
            $resolved = AutoloadResolver::resolveNamespaceToPath($namespace);
            $baseOutputPath = rtrim(
                $resolved . $this->codefy::DS . $directory,
                $this->codefy::DS
            );
        }

        // Ensure directory exists
        if (!is_dir($baseOutputPath)) {
            $this->filesystem->mkdir(path: $baseOutputPath);
        }

        /**
         * ---------------------------------------------------------------------
         * Generate each file in the preset
         * ---------------------------------------------------------------------
         */
        foreach ($preset['files'] as $file) {
            $suffix   = $file['suffix'] ?? '';
            $filename = $className . $suffix . '.php';

            $stubFile = $stubsPath . $this->codefy::DS . $file['stub'];
            $content  = $this->filesystem->getContents($stubFile);

            // Normalize namespace (remove duplicates + trailing slash)
            $namespace = preg_replace('#\\\\+#', '\\', $namespace);
            $namespace = rtrim($namespace, '\\');

            // Replace placeholders
            $content = str_replace(
                ['{{ namespace }}', '{{ class }}'],
                [$namespace, $className . $suffix],
                $content
            );

            // Final path: folder + filename
            $filePath = $baseOutputPath . $this->codefy::DS . $filename;

            // Normalize slashes
            $filePath = preg_replace('#/+#', '/', $filePath);

            // Write file
            $this->filesystem->putContents($filePath, $content);

            $created[] = $filePath;
        }

        return $created;
    }
}

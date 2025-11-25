<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Codefy\Framework\Application;
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
     * @throws Exception
     */
    public function generate(
        array $preset,
        string $namespace,
        string $directory,
        string $className
    ): array {
        $created = [];
        $stubsPath = $this->configContainer->getConfigKey(key: 'stubs.stubs_path');

        $fullBasePath = rtrim(
            AutoloadResolver::resolveNamespaceToPath($namespace) . $this->codefy::DS . $directory,
            $this->codefy::DS
        );

        if (!is_dir($fullBasePath)) {
            $this->filesystem->mkdir($fullBasePath);
        }

        foreach ($preset['files'] as $file) {
            $suffix = $file['suffix'] ?? '';
            $filename = $className . $suffix . '.php';

            $stubFile = $stubsPath . $this->codefy::DS . $file['stub'];
            $content = $this->filesystem->getContents($stubFile);

            // basic replacements
            $content = str_replace(
                ['{{ namespace }}', '{{ class }}'],
                [$namespace, $className . $suffix],
                $content
            );

            $filePath = $fullBasePath . $this->codefy::DS . $filename;

            $this->filesystem->putContents($filePath, $content);
            $created[] = $filePath;
        }

        return $created;
    }
}

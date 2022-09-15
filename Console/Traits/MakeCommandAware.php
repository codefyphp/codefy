<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Traits;

use Codefy\Foundation\Application;
use Codefy\Foundation\Console\Exceptions\MakeCommandFileAlreadyExistsException;
use Codefy\Foundation\Factory\FileLoggerFactory;
use Codefy\Foundation\Support\LocalStorage;
use Exception;
use League\Flysystem\FilesystemException;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\Inflector;
use RuntimeException;

use function glob;
use function Qubus\Support\Helpers\camel_case;
use function Qubus\Support\Helpers\studly_case;

trait MakeCommandAware
{
    /**
     * @throws FilesystemException
     * @throws TypeException
     */
    private function resolveResource(string $resource, mixed $options): void
    {
        if (!empty($resource)) {
            $elements = explode('_', $resource);
            $classNamePrefix = array_shift($elements) ?? null; // class file
            $classNameSuffix = array_pop($elements); // stub file

            /* Lets the resolve the suffix of the class aka stub */
            $this->resolveClassNameSuffix(
                classNameSuffix: $classNameSuffix,
                classNamePrefix: $classNamePrefix,
                options: $options
            );
        }
    }

    /**
     * @param string $classNameSuffix
     * @param string $classNamePrefix
     * @throws TypeException
     * @throws Exception|FilesystemException
     */
    private function resolveClassNameSuffix(
        string $classNameSuffix,
        string $classNamePrefix,
        mixed $options = null
    ): void {
        /* throw an exception if the argument is empty */
        (
            !empty($classNameSuffix) ?: throw new TypeException(
                'Your stub file is invalid or no argument is supplied.'
            )
        );

        (
            in_array($classNameSuffix, array_keys(self::STUBS))
            ?: throw new TypeException(
                'Your stub is an invalid stub. Please refer to the allowable stubs you can create. '
                . implode(', ', array_keys(self::STUBS))
            )
        );

        $file = $this->getStubFiles($classNameSuffix);
        /* replace the placeholder variables with valid strings */
        [
            $newContentStream,
            $qualifiedClass,
            $qualifiedNamespace
        ] = $this->resolveStubContentPlaceholders($file, $classNameSuffix, $classNamePrefix);

        $this->createClassFromStub($qualifiedClass, $newContentStream, $classNameSuffix, $options, $qualifiedNamespace);
    }

    /**
     * Create the class file based on the stub file. Once the file is resolved and have a valid directory path
     * and the stub content is properly filtered and change to reflect. Then and only then we will
     * generate the actual usable class file.
     *
     * Note. realpath will return false if the file or directory does not exist.
     *
     * @param string $qualifiedClass
     * @param string|null $contentStream
     * @param string|null $classNameSuffix
     * @param mixed|null $options
     * @param string|null $qualifiedNamespaces - will return the namespace for the stub command
     * @return void
     * @throws FilesystemException
     * @throws MakeCommandFileAlreadyExistsException
     */
    public function createClassFromStub(
        string $qualifiedClass,
        ?string $contentStream = null,
        ?string $classNameSuffix = null,
        mixed $options = null,
        ?string $qualifiedNamespaces = null
    ): void {
        if ($classNameSuffix === null || $contentStream === null) {
            throw new RuntimeException(
                message: 'Directory could not be created because the 3rd argument returned null.'
            );
        }

        $filePath = Application::$ROOT_PATH. Application::DS
            . $qualifiedNamespaces . $this->addOptionalDirFlag(options: $options);

        if (!is_dir($filePath)) {
            try {
                LocalStorage::disk()->createDirectory($filePath);
            } catch (FilesystemException $ex) {
                FileLoggerFactory::error($ex->getMessage());
            }
        }

        $realFilepath = realpath(path: $filePath);
        $className = $qualifiedClass . self::FILE_EXTENSION;
        $newClassFileAndPath = $realFilepath . Application::DS . $className;

        /* We will need to check $newClassFileAndPath doesn't already exist else this will wipe the content */
        if (file_exists($newClassFileAndPath)) {
            throw new MakeCommandFileAlreadyExistsException(
                sprintf(
                    '%s file already exists. To recreate you will first need to delete the existing file.',
                    $className
                )
            );
        }
        file_put_contents($newClassFileAndPath, $contentStream, LOCK_EX);
    }

    /**
     * console command option flag. Use --dir={directory_name} to add a directory to the end
     * of the filepath to create a subdirectory within a main directory
     *
     * @param mixed $options
     * @return string
     */
    private function addOptionalDirFlag(mixed $options): string
    {
        return (isset($options) && $options !== '' || $options !== null)
            ? Application::DS . Inflector::wordsToUpper($options) :
            '';
    }

    /**
     * Uses the php glob to retrieve all stub files form the relevant directory. Which will return
     * an array of files within the specified directory with the [.stub] extension.
     * We then iterate over that array and uses php str_contain function to match a file from
     * the array with the classNameSuffix. When we have a match then return the matching file string.
     *
     * @param string $classNameSuffix
     * @return string|false
     */
    private function getStubFiles(string $classNameSuffix): string|false
    {
        //$files = glob(Application::$ROOT_PATH. '/vendor/magmacore/magmacore/src/Stubs/*.stub');
        $files = glob(Application::$ROOT_PATH. '/stubs/*.stub');
        if (is_array($files) && count($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (str_contains($file, ucwords($classNameSuffix))) {
                        /* return the matching file bases on the class name suffix */
                        return $file;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param string $file
     * @param string $classNameSuffix
     * @param string $classNamePrefix
     * @return array|bool
     */
    private function resolveStubContentPlaceholders(
        string $file,
        string $classNameSuffix,
        string $classNamePrefix
    ): array|bool {
        if ($file) {
            $contentStream = file_get_contents($file);
            if ($contentStream !='') {
                $patterns = [
                    '{{ class }}',
                    '{{ namespace }}',
                    '{{ property }}',
                    '{{ table_name }}',
                    '{{ modelName }}',
                    '{{ modelVar }}'
                ];

                foreach ($patterns as $pattern) {
                    if (str_contains($contentStream, $pattern)) {
                        if (isset($pattern) && $pattern !='') {
                            $qualifiedClass = studly_case($classNamePrefix . ucwords($classNameSuffix));

                            $qualifiedNamespace = array_filter(
                                self::STUBS,
                                fn ($value, $key) => $value,
                                ARRAY_FILTER_USE_BOTH
                            );

                            $_namespace = '';

                            $stubFile = strrchr($file, '/');
                            $stubFile = str_replace(['/Example', '.stub'], '', $stubFile);
                            $_namespace = '';
                            foreach ($qualifiedNamespace as $namespace) {
                                if (str_contains($namespace, $stubFile)) {
                                    $_namespace = $namespace;
                                    continue;
                                }
                            }

                            /* resolve table_name placeholder for model class */
                            $tableName = Inflector::pluralize($classNamePrefix) ?? '';
                            /* fill the property placeholder */
                            $property = camel_case($classNamePrefix . ucwords($classNameSuffix)) ?? '';
                            /* resolve class which uses a model as a dependency */
                            [$modelName, $modelVar] = $this->resolveModelDependency($classNamePrefix, $classNameSuffix);
                            $newContentStream = str_replace(
                                $patterns,
                                [$qualifiedClass, $_namespace . ';', $property, $tableName, $modelName, $modelVar],
                                $contentStream
                            );

                            return [
                                $newContentStream,
                                $qualifiedClass,
                                $_namespace
                            ];
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Resolve the model dependency by specifying which stubs class will require a model.
     *
     * @param string $classNamePrefix
     * @param string $classNameSuffix
     * @return array|bool
     */
    private function resolveModelDependency(string $classNamePrefix, string $classNameSuffix): array|bool
    {
        if ($classNameSuffix === 'fillable' || $classNameSuffix === 'schema' || $classNameSuffix === 'repository') {
            $model = studly_case($classNamePrefix . 'Model');
            $property = camel_case($classNamePrefix . 'Model') ?? '';
            return [
                $model,
                $property
            ];
        }
        return false;
    }
}

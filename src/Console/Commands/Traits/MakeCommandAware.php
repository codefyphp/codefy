<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands\Traits;

use Codefy\Framework\Console\Exceptions\MakeCommandFileAlreadyExistsException;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Support\LocalStorage;
use Exception;
use League\Flysystem\FilesystemException;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\Inflector;
use ReflectionException;
use RuntimeException;

use function str_contains;
use function glob;
use function Qubus\Support\Helpers\camel_case;
use function Qubus\Support\Helpers\studly_case;
use function str_replace;

/** @deprecated 3.1 Will be removed in version 4. */
trait MakeCommandAware
{
    /**
     * @throws TypeException
     * @throws MakeCommandFileAlreadyExistsException
     * @throws ReflectionException
     */
    private function resolveResource(string $resource, mixed $options): void
    {
        if (!empty($resource)) {
            $elements = explode(separator: '_', string: $resource);
            $classNamePrefix = array_shift($elements); // class file
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
     * @param mixed|null $options
     * @throws MakeCommandFileAlreadyExistsException
     * @throws ReflectionException
     * @throws TypeException
     * @throws Exception
     */
    private function resolveClassNameSuffix(
        string $classNameSuffix,
        string $classNamePrefix,
        mixed $options = null
    ): void {
        /* throw an exception if the argument is empty */
        (
        !empty($classNameSuffix) ?: throw new TypeException(
            message: 'Your stub file is invalid or no argument is supplied.'
        )
        );

        (
        in_array(needle: $classNameSuffix, haystack: array_keys(array: self::STUBS))
        ?: throw new TypeException(
            message: 'Your stub is an invalid stub. Please refer to the allowable Stubs you can create. '
                . implode(separator: ', ', array: array_keys(array: self::STUBS))
        )
        );

        $file = $this->getStubFiles(classNameSuffix: $classNameSuffix);
        /* replace the placeholder variables with valid strings */
        [
            $newContentStream,
            $qualifiedClass,
            $qualifiedNamespace
        ] = $this->resolveStubContentPlaceholders(
            file: $file,
            classNameSuffix: $classNameSuffix,
            classNamePrefix: $classNamePrefix
        );

        $this->createClassFromStub(
            qualifiedClass: $qualifiedClass,
            contentStream: $newContentStream,
            classNameSuffix: $classNameSuffix,
            flag: $options,
            qualifiedNamespaces: $qualifiedNamespace
        );
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
     * @param string|null $flag
     * @param string|null $qualifiedNamespaces - will return the namespace for the stub command
     * @return void
     * @throws MakeCommandFileAlreadyExistsException
     * @throws Exception
     * @throws ReflectionException
     */
    public function createClassFromStub(
        string $qualifiedClass,
        ?string $contentStream = null,
        ?string $classNameSuffix = null,
        ?string $flag = null,
        ?string $qualifiedNamespaces = null
    ): void {
        if ($classNameSuffix === null || $contentStream === null) {
            throw new RuntimeException(
                message: 'Directory could not be created because the 3rd argument returned null.'
            );
        }

        $filePath = $this->codefy::$ROOT_PATH . $this->codefy::DS
        . $qualifiedNamespaces . $this->addOptionalDirFlag(flag: $flag);

        $normalizePath = str_replace(search: '\\', replace: $this->codefy::DS, subject: $filePath);

        if (!is_dir(filename: $normalizePath)) {
            try {
                LocalStorage::disk()->createDirectory(location: $normalizePath);
            } catch (FilesystemException $ex) {
                FileLoggerFactory::error(message: $ex->getMessage());
            } catch (Exception $ex) {
                FileLoggerFactory::error(message: $ex->getMessage());
            }
        }

        $realFilepath = realpath(path: $normalizePath);
        $className = $qualifiedClass . self::FILE_EXTENSION;
        $newClassFileAndPath = $realFilepath . $this->codefy::DS . $className;

        /* We will need to check $newClassFileAndPath doesn't already exist else this will wipe the content */
        if (file_exists(filename: $newClassFileAndPath)) {
            throw new MakeCommandFileAlreadyExistsException(
                message: sprintf(
                    '%s file already exists. To recreate you will first need to delete the existing file.',
                    $className
                )
            );
        }
        file_put_contents(filename: $newClassFileAndPath, data: $contentStream, flags: LOCK_EX);
    }

    /**
     * console command option flag. Use --dir={directory_name} to add a directory to the end
     * of the filepath to create a subdirectory within a main directory
     *
     * @param string $flag
     * @return string
     */
    private function addOptionalDirFlag(string $flag): string
    {
        return $flag !== ''
        ? $this->codefy::DS . Inflector::wordsToUpper(class: $flag) :
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
        $files = glob(pattern: $this->codefy::$ROOT_PATH . '/vendor/codefyphp/codefy/src/Stubs/*.stub');
        if (is_array(value: $files) && count($files)) {
            foreach ($files as $file) {
                if (is_file(filename: $file)) {
                    if (str_contains($file, ucwords(string: $classNameSuffix))) {
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
            $contentStream = file_get_contents(filename: $file);
            if ($contentStream !== '') {
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
                        $qualifiedClass = studly_case(string: $classNamePrefix . ucwords(string: $classNameSuffix));

                        $qualifiedNamespace = array_filter(
                            array: self::STUBS,
                            callback: fn ($value, $key) => $value, // @phpstan-ignore argument.type
                            mode: ARRAY_FILTER_USE_BOTH
                        );

                        $_namespace = '';

                        $stubFile = strrchr(haystack: $file, needle: '/');
                        $stubFile = str_replace(search: ['/Example', '.stub'], replace: '', subject: $stubFile);
                        foreach ($qualifiedNamespace as $namespace) {
                            if (str_contains($namespace, $stubFile)) {
                                $_namespace = $namespace;
                                continue;
                            }
                        }

                        /* resolve table_name placeholder for model class */
                        $tableName = Inflector::pluralize(word: $classNamePrefix);
                        /* fill the property placeholder */
                        $property = camel_case(str: $classNamePrefix . ucwords(string: $classNameSuffix));
                        /* resolve class which uses a model as a dependency */
                        [$modelName, $modelVar] = $this->resolveModelDependency(
                            classNamePrefix: $classNamePrefix,
                            classNameSuffix: $classNameSuffix
                        );
                        $newContentStream = str_replace(
                            search: $patterns,
                            replace: [
                                $qualifiedClass, $_namespace . ';', $property, $tableName, $modelName, $modelVar
                            ],
                            subject: $contentStream
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
        return false;
    }

    /**
     * Resolve the model dependency by specifying which Stubs class will require a model.
     *
     * @param string $classNamePrefix
     * @param string $classNameSuffix
     * @return array|bool
     */
    private function resolveModelDependency(string $classNamePrefix, string $classNameSuffix): array|bool
    {
        if ($classNameSuffix === 'fillable' || $classNameSuffix === 'schema' || $classNameSuffix === 'repository') {
            $model = studly_case(string: $classNamePrefix . 'Model');
            $property = camel_case(str: $classNamePrefix . 'Model');
            return [
                $model,
                $property
            ];
        }
        return false;
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use ArrayAccess;
use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Migration\Adapter\MigrationAdapter;
use Codefy\Framework\Migration\Migration;
use Codefy\Framework\Migration\Migrator;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class PhpMigCommand extends ConsoleCommand
{
    protected ?ArrayAccess $objectmap = null;

    protected ?MigrationAdapter $adapter = null;

    protected ?string $bootstrap = null;

    protected array $migrations = [];

    protected function configure(): void
    {
        $this->addOption(
            name: '--set',
            shortcut: '-s',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The phpmig.sets key.'
        );
        $this->addOption(
            name: '--bootstrap',
            shortcut: '-b',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The bootstrap file to load.'
        );
    }

    /**
     * Bootstrap migration.
     *
     * @return void
     * @throws Exception
     */
    protected function bootstrap(InputInterface $input, OutputInterface $output): void
    {
        $this->setBootstrap(bootstrap: $this->findBootstrapFile($input->getOption(name: 'bootstrap')));

        $objectmap = $this->bootstrapObjectMap();
        $this->setObjectMap(objectmap: $objectmap);

        $this->setAdapter(adapter: $this->bootstrapAdapter(input: $input));

        $this->setMigrations(migrations: $this->bootstrapMigrations(input: $input, output: $output));

        $objectmap['phpmig.migrator'] = $this->bootstrapMigrator(output: $output);
    }

    protected function findBootstrapFile($filename): string
    {
        if (null === $filename) {
            $filename = 'phpmig.php';
        }

        return $this->codefy->bootStrapPath() . Application::DS . $filename;
    }

    protected function bootstrapObjectMap(): ArrayAccess
    {
        $bootstrapFile = $this->getBootstrap();

        $func = function () use ($bootstrapFile) {
            return require $bootstrapFile;
        };

        $objectmap = $func();

        if (!($objectmap instanceof ArrayAccess)) {
            throw new RuntimeException(message: $bootstrapFile . ' must return object of type ArrayAccess');
        }

        return $objectmap;
    }

    /**
     * @param InputInterface $input
     * @return MigrationAdapter
     * @throws RuntimeException
     */
    protected function bootstrapAdapter(InputInterface $input): MigrationAdapter
    {
        $objectmap = $this->getObjectMap();

        $validAdapter = isset($objectmap['phpmig.adapter']);
        $validSets = !isset($objectmap['phpmig.sets']) || is_array($objectmap['phpmig.sets']);

        if (!$validAdapter && !$validSets) {
            throw new RuntimeException(
                message: sprintf(
                    '%s must return data with phpmig.adapter or phpmig.sets',
                    $this->getBootstrap()
                )
            );
        }

        if (isset($objectmap['phpmig.sets'])) {
            $set = $input->getOption(name: 'set');
            if (!isset($objectmap['phpmig.sets'][$set]['adapter'])) {
                throw new RuntimeException(
                    message: $set . ' has undefined keys or adapter at phpmig.sets'
                );
            }
            $adapter = $objectmap['phpmig.sets'][$set]['adapter'];
        }

        if (isset($objectmap['phpmig.adapter'])) {
            $adapter = $objectmap['phpmig.adapter'];
        }

        if (!($adapter instanceof MigrationAdapter)) {
            throw new RuntimeException(
                message: "phpmig.adapter or phpmig.sets must be an 
                instance of \\Codefy\\Foundation\\Migration\\Adapter\\MigrationAdapter"
            );
        }

        if (!$adapter->hasSchema()) {
            $adapter->createSchema();
        }

        return $adapter;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     * @throws TypeException
     * @throws Exception
     */
    protected function bootstrapMigrations(InputInterface $input, OutputInterface $output): array
    {
        $objectmap = $this->getObjectMap();
        $set = $input->getOption(name: 'set');

        if (!isset($objectmap['phpmig.migrations'])
            && !isset($objectmap['phpmig.migrations_path'])
            && (isset($objectmap['phpmig.sets'])
                && !isset($objectmap['phpmig.sets'][$set]['migrations_path']))) {
            throw new RuntimeException(
                message: $this->getBootstrap() . ' must return phpmig.migrations array 
                or migrations default path at phpmig.migrations_path 
                or migrations default path at phpmig.sets'
            );
        }

        $migrations = [];

        if (isset($objectmap['phpmig.migrations'])) {
            if (!is_array(value: $objectmap['phpmig.migrations'])) {
                throw new RuntimeException(
                    message: $this->getBootstrap() . ' phpmig.migrations must be an array.'
                );
            }

            $migrations = $objectmap['phpmig.migrations'];
        }
        if (isset($objectmap['phpmig.migrations_path'])) {
            if (!is_dir(filename: $objectmap['phpmig.migrations_path'])) {
                throw new RuntimeException(
                    message: $this->getBootstrap() . ' phpmig.migrations_path must be a directory.'
                );
            }

            $migrationsPath = realpath(path: $objectmap['phpmig.migrations_path']);
            $migrations = array_merge($migrations, glob(pattern: $migrationsPath . Application::DS . '*.php'));
        }
        if (isset($objectmap['phpmig.sets']) && isset($objectmap['phpmig.sets'][$set]['migrations_path'])) {
            if (!is_dir(filename: $objectmap['phpmig.sets'][$set]['migrations_path'])) {
                throw new RuntimeException(
                    message: $this->getBootstrap() . " ['phpmig.sets']['" . $set . "']['migrations_path'] 
                    must be a directory."
                );
            }

            $migrationsPath = realpath(path: $objectmap['phpmig.sets'][$set]['migrations_path']);
            $migrations = array_merge($migrations, glob(pattern: $migrationsPath . Application::DS . '*.php'));
        }
        $migrations = array_unique(array: $migrations);

        $versions = [];
        $names = [];

        foreach ($migrations as $path) {
            if (!preg_match('/^[0-9]+/', basename($path), $matches)) {
                throw new TypeException(
                    message: sprintf('The file "%s" does not have a valid migration filename.', $path)
                );
            }

            $version = $matches[0];
            if (isset($versions[$version])) {
                throw new TypeException(
                    message: sprintf(
                        'Duplicate migration, "%s" has the same version as "%s".',
                        $path,
                        $versions[$version]->getName()
                    )
                );
            }

            $migrationName = preg_replace(pattern: '/^[0-9]+_/', replacement: '', subject: basename(path: $path));
            if (false !== strpos(haystack: $migrationName, needle: '.')) {
                $migrationName = substr(
                    string: $migrationName,
                    offset: 0,
                    length: strpos(haystack: $migrationName, needle: '.')
                );
            }

            $class = $this->migrationToClassName(migrationName: $migrationName);

            if ($this instanceof GenerateCommand
                && $class == $this->migrationToClassName(migrationName: $input->getArgument(name: 'name'))) {
                throw new TypeException(
                    message: sprintf(
                        'Migration Class "%s" already exists',
                        $class
                    )
                );
            }

            if (isset($names[$class])) {
                throw new TypeException(
                    message: sprintf(
                        'Migration "%s" has the same name as "%s"',
                        $path,
                        $names[$class]
                    )
                );
            }
            $names[$class] = $path;

            require_once $path;

            if (!class_exists(class: $class)) {
                throw new TypeException(
                    message: sprintf(
                        'Could not find class "%s" in file "%s"',
                        $class,
                        $path
                    )
                );
            }

            $migration = new $class($version);

            if (!($migration instanceof Migration)) {
                throw new TypeException(
                    message: sprintf(
                        'The class "%s" in file "%s" must extend \Codefy\Framework\Migration\Migration',
                        $class,
                        $path
                    )
                );
            }

            $migration->setOutput(output: $output); // inject output

            $versions[$version] = $migration;
        }

        if (isset($objectmap['phpmig.sets']) && isset($objectmap['phpmig.sets'][$set]['connection'])) {
            $objectmap['phpmig.connection'] = $objectmap['phpmig.sets'][$set]['connection'];
        }

        ksort($versions);

        return $versions;
    }

    /**
     * @param OutputInterface $output
     * @return mixed
     */
    protected function bootstrapMigrator(OutputInterface $output): mixed
    {
        return new Migrator(adapter: $this->getAdapter(), objectmap: $this->getObjectMap(), output: $output);
    }

    /**
     * Set bootstrap.
     *
     * @param string $bootstrap
     * @return PhpMigCommand
     */
    public function setBootstrap(string $bootstrap): static
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

    /**
     * Get bootstrap
     *
     * @return string|null
     */
    public function getBootstrap(): ?string
    {
        return $this->bootstrap;
    }

    /**
     * Set migrations
     *
     * @param array $migrations
     * @return PhpMigCommand
     */
    public function setMigrations(array $migrations): static
    {
        $this->migrations = $migrations;
        return $this;
    }

    /**
     * Get migrations.
     *
     * @return array
     */
    public function getMigrations(): array
    {
        return $this->migrations;
    }

    /**
     * Set objectmap.
     *
     * @param ArrayAccess $objectmap
     * @return PhpMigCommand
     */
    public function setObjectMap(ArrayAccess $objectmap): static
    {
        $this->objectmap = $objectmap;
        return $this;
    }

    /**
     * Get objectmap.
     *
     * @return ArrayAccess|null
     */
    public function getObjectMap(): ?ArrayAccess
    {
        return $this->objectmap;
    }


    /**
     * Set adapter.
     *
     * @param MigrationAdapter $adapter
     * @return PhpMigCommand
     */
    public function setAdapter(MigrationAdapter $adapter): static
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Get Adapter
     *
     * @return MigrationAdapter|null
     */
    public function getAdapter(): ?MigrationAdapter
    {
        return $this->adapter;
    }

    /**
     * Transform create_table_user to CreateTableUser
     *
     * @param $migrationName
     * @return string
     * @throws TypeException
     */
    protected function migrationToClassName($migrationName): string
    {
        $class = str_replace(search: '_', replace: ' ', subject: $migrationName);
        $class = ucwords(string: $class);
        $class = str_replace(search: ' ', replace: '', subject: $class);

        if (!$this->isValidClassName(className: $class)) {
            throw new TypeException(
                message: sprintf(
                    'Migration class "%s" is invalid',
                    $class
                )
            );
        }

        return $class;
    }

    /**
     * @param $className
     * @return bool
     * @see http://php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class
     */
    private function isValidClassName($className): bool
    {
        return preg_match(pattern: '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', subject: $className) === 1;
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Application;
use Codefy\Foundation\Console\ConsoleCommand;
use Codefy\Foundation\Migration\Adapter\MigrationDatabaseAdapter;
use Codefy\Foundation\Migration\Migration;
use Codefy\Foundation\Migration\Migrator;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class PhpMigCommand extends ConsoleCommand
{
    protected ConfigContainer $config;
    /**
     * @var ?MigrationDatabaseAdapter
     */
    protected ?MigrationDatabaseAdapter $adapter = null;

    /**
     * @var ?string
     */
    protected ?string $bootstrap = null;

    /**
     * @var array
     */
    protected array $migrations = [];

    public function __construct(protected Application $codefy)
    {
        $this->config = $this->codefy->make(name: 'codefy.config');
        parent::__construct(codefy: $this->codefy);
    }

    protected array $options = [
        [
            '--set',
            '-s',
            'required',
            'The database.migrations.sets key',
            false
        ],
    ];

    /**
     * Bootstrap migration.
     *
     * @return void
     * @throws Exception
     */
    protected function bootstrap(InputInterface $input, OutputInterface $output): void
    {
        $this->setAdapter(adapter: $this->bootstrapAdapter(input: $input));

        $this->setMigrations(migrations: $this->bootstrapMigrations(input: $input, output: $output));

        $this->config->setConfigKey('database.phpmig.migrator', $this->bootstrapMigrator(output: $output));
    }

    /**
     * @param InputInterface $input
     * @return MigrationDatabaseAdapter
     * @throws RuntimeException
     * @throws Exception
     */
    protected function bootstrapAdapter(InputInterface $input)
    {
        $validAdapter = $this->config->getConfigKey('database.phpmig.adapter') !== null;
        $validSets = $this->config->getConfigKey('database.phpmig.sets') === null
            || is_array(value: $this->config->getConfigKey('database.phpmig.sets'));

        if (!$validAdapter && !$validSets) {
            throw new RuntimeException(
                message: 'Database config must return data with database.phpmig.adapter or database.phpmig.sets'
            );
        }

        if ($this->config->getConfigKey('database.phpmig.sets') !== null) {
            $set = $input->getOption(name: 'set');
            if ($this->config->getConfigKey('database.phpmig.sets')[$set]['adapter'] === null) {
                throw new RuntimeException(
                    message: $set . ' has undefined keys or adapter at database.phpmig.sets'
                );
            }
            $adapter = $this->config->getConfigKey('database.phpmig.sets')[$set]['adapter'];
        }
        if ($this->config->getConfigKey('database.phpmig.adapter') !== null) {
            $adapter = $this->config->getConfigKey('database.phpmig.adapter');
        }

        if (!($adapter instanceof MigrationDatabaseAdapter)) {
            throw new RuntimeException(
                message: "database.phpmig.adapter or database.phpmig.sets must be an 
                instance of \\Codefy\\Foundation\\Migration\\Adapter\\DatabaseAdapter"
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
        $set = $input->getOption(name: 'set');

        if ($this->config->getConfigKey('database.phpmig.migrations') === null
            && $this->config->getConfigKey('database.phpmig.migrations_path') === null
            && ($this->config->getConfigKey('database.phpmig.sets') !== null)
                && $this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_path'] === null) {
            throw new RuntimeException(
                message: $this->getBootstrap() . ' must return database.phpmig.migrations array 
                or migrations default path at database.phpmig.migrations_path 
                or migrations default path at database.phpmig.sets'
            );
        }

        $migrations = [];

        if ($this->config->getConfigKey('database.phpmig.migrations') !== null) {
            if (!is_array(value: $this->config->getConfigKey('database.phpmig.migrations'))) {
                throw new RuntimeException(
                    message: $this->getBootstrap() . ' database.phpmig.migrations must be an array.'
                );
            }

            $migrations = $this->config->getConfigKey('database.phpmig.migrations');
        }
        if ($this->config->getConfigKey('database.phpmig.migrations_path') !== null) {
            if (!is_dir(filename: $this->config->getConfigKey('database.phpmig.migrations_path'))) {
                throw new RuntimeException(
                    message: $this->getBootstrap() . ' database.phpmig.migrations_path must be a directory.'
                );
            }

            $migrationsPath = realpath(path: $this->config->getConfigKey('database.phpmig.migrations_path'));
            $migrations = array_merge($migrations, glob(pattern: $migrationsPath . Application::DS . '*.php'));
        }
        if ($this->config->getConfigKey('database.phpmig.sets') !== null
            && $this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_path'] !== null) {
            if (!is_dir(filename: $this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_path'])) {
                throw new RuntimeException(
                    message: $this->getBootstrap() . " ['database.phpmig.sets']['" . $set . "']['migrations_path'] 
                    must be a directory."
                );
            }

            $migrationsPath = realpath(
                path: $this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_path']
            );
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

            $migrationName = preg_replace(pattern: '/^[0-9]+_/', replacement: '', subject: basename($path));
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
                        'The class "%s" in file "%s" must extend \Codefy\Foundation\Migration\Migration',
                        $class,
                        $path
                    )
                );
            }

            $migration->setOutput(output: $output); // inject output

            $versions[$version] = $migration;
        }

        if ($this->config->getConfigKey('database.phpmig.sets') !== null
            && $this->config->getConfigKey('database.phpmig.sets')[$set]['adapter'] !== null) {
            $this->config->setConfigKey(
                'database.phpmig.adapter',
                $this->config->getConfigKey('database.phpmig.sets')[$set]['adapter']
            );
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
        return new Migrator(adapter: $this->getAdapter(), output: $output);
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
     * Set adapter.
     *
     * @param MigrationDatabaseAdapter $adapter
     * @return PhpMigCommand
     */
    public function setAdapter(MigrationDatabaseAdapter $adapter): static
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Get Adapter
     *
     * @return MigrationDatabaseAdapter|null
     */
    public function getAdapter(): ?MigrationDatabaseAdapter
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

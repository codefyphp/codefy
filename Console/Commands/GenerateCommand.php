<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Application;
use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use RuntimeException;

use function Qubus\Support\Helpers\is_writable;

class GenerateCommand extends PhpMigCommand
{
    protected string $name = 'migrate:generate';

    protected string $description = 'Generate a new migration.';

    protected string $help = <<<EOT
The <info>migrate:generate</info> command creates a new migration with the name and path specified
<info>php codex migrate:generate CreatePostTable ./migrations</info>
EOT;

    protected array $args = [
        [
            'name',
            'required',
            'The name of the migration.'
        ],
        [
            'path',
            'optional',
            'The directory in which to put the migration ( optional if database.phpmig.migrations_path is set. )'
        ],
    ];

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->bootstrap(input: $this->input, output: $this->output);

        $path = $this->getArgument(key: 'path');
        $set = $this->getOptions(key: 'set');

        if (null === $path) {
            if ($this->config->getConfigKey('database.phpmig.migrations_path') !== null) {
                $path = $this->config->getConfigKey('database.phpmig.migrations_path');
            }
            // do not deep link to nested keys without first testing the parent key
            if ($this->config->getConfigKey('database.phpmig.sets') !== null) {
                if ($this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_path'] !== null) {
                    $path = $this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_path'];
                }
            }
        }

        if (!is_writable(path: $path)) {
            throw new TypeException(
                message: sprintf(
                    'The directory "%s" is not writeable',
                    $path
                )
            );
        }

        $path = realpath(path: $path);

        $migrationName = $this->transMigName(migrationName: $this->getArgument('name'));

        $basename = date(format: 'YmdHis') . '_' . $migrationName . '.php';

        $path = $path . Application::DS . $basename;

        if (file_exists(filename: $path)) {
            throw new TypeException(
                message: sprintf(
                    'The file "%s" already exists',
                    $path
                )
            );
        }

        $className = $this->migrationToClassName(migrationName: $migrationName);

        if ($this->config->getConfigKey('database.phpmig.migrations_template_path') !== null
            || ($this->config->getConfigKey('database.phpmig.sets') !== null)
            && $this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_template_path'] !== null) {
            if ($this->config->getConfigKey('database.phpmig.migrations_template_path') !== null) {
                $migrationsTemplatePath = $this->config->getConfigKey('database.phpmig.migrations_template_path');
            } else {
                $migrationsTemplatePath =
                    $this->config->getConfigKey('database.phpmig.sets')[$set]['migrations_template_path'];
            }

            if (false === file_exists(filename: $migrationsTemplatePath)) {
                throw new RuntimeException(
                    message: sprintf(
                        'The template file "%s" not found',
                        $migrationsTemplatePath
                    )
                );
            }

            if (preg_match(pattern: '/\.php$/', subject: $migrationsTemplatePath)) {
                ob_start();
                include $migrationsTemplatePath;
                $contents = ob_get_clean();
            } else {
                $contents = file_get_contents(filename: $migrationsTemplatePath);
                $contents = sprintf($contents, $className);
            }
        } else {
            $contents = <<<PHP
<?php

declare(strict_types=1);

use Codefy\Foundation\Migration\Migration;

class $className extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
    }
    
    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
PHP;
        }

        if (false === file_put_contents(filename: $path, data: $contents)) {
            throw new RuntimeException(
                message: sprintf(
                    'The file "%s" could not be written to',
                    $path
                )
            );
        }

        $this->terminalRaw(
            string: '<info>+f</info> ' .
            '.' . str_replace(search: getcwd(), replace: '', subject: $path)
        );

        return ConsoleCommand::SUCCESS;
    }

    protected function transMigName($migrationName): string
    {
        //http://php.net/manual/en/language.variables.basics.php
        if (preg_match(pattern: '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', subject: $migrationName)) {
            return $migrationName;
        }
        return 'mig' . $migrationName;
    }
}

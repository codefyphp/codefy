<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use RuntimeException;

use Symfony\Component\Console\Input\InputArgument;

use function Qubus\Support\Helpers\is_writable;

class GenerateCommand extends PhpMigCommand
{
    protected string $name = 'migrate:generate';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument(name: 'name', mode: InputArgument::REQUIRED, description: 'The name for the migration.')
            ->addArgument(
                name: 'path',
                mode: InputArgument::OPTIONAL,
                description: 'The directory in which to put the migration 
                ( optional if phpmig.migrations_path is set ).'
            )
            ->setDescription(description: 'Generate a new migration.')
            ->setHelp(
                help: <<<EOT
The <info>migrate:generate</info> command creates a new migration with the name and path specified
<info>php codex migrate:generate CreatePostTable</info>
EOT
            );
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->bootstrap(input: $this->input, output: $this->output);

        $path = $this->getArgument(key: 'path');
        $set = $this->getOptions(key: 'set');

        if (null === $path || '' === $path) {
            if (true === isset($this->objectmap['phpmig.migrations_path'])) {
                $path = $this->objectmap['phpmig.migrations_path'];
            }
            // do not deep link to nested keys without first testing the parent key
            if (true === isset($this->objectmap['phpmig.sets'])) {
                if (true === isset($this->objectmap['phpmig.sets'][$set]['migrations_path'])) {
                    $path = $this->objectmap['phpmig.sets'][$set]['migrations_path'];
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

        if (isset($this->objectmap['phpmig.migrations_template_path'])
            || (isset($this->objectmap['phpmig.sets'])
                && isset($this->objectmap['phpmig.sets'][$set]['migrations_template_path']))) {
            if (true === isset($this->objectmap['phpmig.migrations_template_path'])) {
                $migrationsTemplatePath = $this->objectmap['phpmig.migrations_template_path'];
            } else {
                $migrationsTemplatePath = $this->objectmap['phpmig.sets'][$set]['migrations_template_path'];
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

use Codefy\Framework\Migration\Migration;

class $className extends Migration
{
    /**
     * Do the migration
     */
    public function up(): void
    {
    }
    
    /**
     * Undo the migration
     */
    public function down(): void
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

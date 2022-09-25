<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Application;
use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends PhpMigCommand
{
    protected string $name = 'migrate:init';

    protected function configure(): void
    {
        $this
            ->setDescription(description: 'Initialise this directory for use with phpmig.')
            ->setHelp(
                help: <<<EOT
The <info>migrate:init</info> command creates a skeleton bootstrap file and a migrations directory.
<info>php codex migrate:init</info>
EOT
            );
    }

    public function handle(): int
    {
        $bootstrap = $this->codefy->bootStrapPath() . Application::DS . 'phpmig.php';
        $migrations = $this->codefy->databasePath() . Application::DS . 'migrations';

        $this->initMigrationsDir($migrations, $this->output);
        $this->initBootstrap($bootstrap, $migrations, $this->output);

        return ConsoleCommand::SUCCESS;
    }

    /**
     * Create migrations dir
     *
     * @return void
     */
    protected function initMigrationsDir($migrations, OutputInterface $output): void
    {
        if (file_exists(filename: $migrations) && is_dir(filename: $migrations)) {
            $output->writeln(
                messages: '<info>--</info> ' .
                $migrations . ' already exists -' .
                ' <comment>Place your migration files in here.</comment>'
            );
            return;
        }

        if (false === mkdir($migrations)) {
            throw new RuntimeException(message: sprintf('Could not create directory "%s".', $migrations));
        }

        $output->writeln(
            messages: '<info>+d</info> ' .
            $migrations .
            ' <comment>Place your migration files in here.</comment>'
        );
    }

    /**
     * Create bootstrap
     *
     * @param string $bootstrap where to put bootstrap file.
     * @param string $migrations path to migrations dir relative to bootstrap.
     * @return void
     */
    protected function initBootstrap(string $bootstrap, string $migrations, OutputInterface $output): void
    {
        if (file_exists(filename: $bootstrap)) {
            $output->writeln(
                messages: '<info>--</info> ' .
                $bootstrap . ' already exists -' .
                ' <comment>Create services in here.</comment>'
            );
            return;
        }

        if (!is_writable(filename: dirname(path: $bootstrap))) {
            throw new RuntimeException(message: sprintf('The file "%s" is not writeable.', $bootstrap));
        }

        $contents = <<<PHP
<?php

declare(strict_types=1);

use \Codefy\Foundation\Migration\Adapter\FileMigrationAdapter;
use \Qubus\Support\Container\ObjectStorageMap;

\$objectmap = new ObjectStorageMap();
// replace this with a better implementation of Codefy\Foundation\Migration\Adapter\MigrationAdapter
\$objectmap['phpmig.adapter'] = new FileMigrationAdapter('$migrations/.migrations.log');
\$objectmap['phpmig.migrations_path'] = '$migrations';

return \$objectmap;
PHP;

        if (false === file_put_contents(filename: $bootstrap, data: $contents)) {
            throw new RuntimeException(message: sprintf('The file "%s" could not be written to.', $bootstrap));
        }

        $output->writeln(
            messages: '<info>+f</info> ' .
            $bootstrap .
            ' <comment>Create services in here.</comment>'
        );
    }
}

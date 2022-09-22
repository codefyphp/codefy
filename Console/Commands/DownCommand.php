<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;

class DownCommand extends PhpMigCommand
{
    protected string $name = 'migrate:down';

    protected string $description = 'Revert a specific migration.';

    protected string $help = <<<EOT
The <info>migrate:down</info> command reverts a specific migration
<info>php codex migrate:down 20111018185412</info>
EOT;

    protected array $args = [
        [
            'version',
            'required',
            'The version number for the migration.'
        ],
    ];

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->bootstrap(input: $this->input, output: $this->output);

        $migrations = $this->getMigrations();
        $versions   = $this->getAdapter()->fetchAll();

        $version = $this->getArgument(key: 'version');

        if (!in_array(needle: $version, haystack: $versions)) {
            return ConsoleCommand::SUCCESS;
        }

        if (!isset($migrations[$version])) {
            return ConsoleCommand::SUCCESS;
        }

        $this->config->getConfigKey('database.phpmig.migrator')->down($migrations[$version]);

        return ConsoleCommand::SUCCESS;
    }
}

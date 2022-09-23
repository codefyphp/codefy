<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;

class UpCommand extends PhpMigCommand
{
    protected string $name = 'migrate:up';

    protected string $description = 'Run a specific migration.';

    protected string $help = <<<EOT
The <info>migrate:up</info> command runs a specific migration
<info>php codex migrate:up 20111018185121</info>
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

        if (in_array(needle: $version, haystack: $versions)) {
            return 0;
        }

        if (!isset($migrations[$version])) {
            return 0;
        }

        $objectmap = $this->getObjectMap();
        $objectmap['phpmig.migrator']->up($migrations[$version]);

        return ConsoleCommand::SUCCESS;
    }
}

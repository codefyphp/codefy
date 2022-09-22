<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;

class RollbackCommand extends PhpMigCommand
{
    protected string $name = 'migrate:rollback';

    protected string $description = 'Rollback to the last, or to a specific migration.';

    protected string $help = <<<EOT
The <info>migrate:rollback</info> command reverts the last migration, or optionally up to a specific version
<info>php codex migrate:rollback</info>
<info>php codex migrate:rollback -t 20111018185412</info>
EOT;

    protected array $options = [
        [
            '--target',
            '-t',
            'optional',
            'The version number to rollback to.',
            false
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

        $version = $this->getOptions(key: 'target');

        ksort($migrations);
        sort($versions);

        // Check we have at least 1 migration to revert
        if (empty($versions) || $version == end($versions)) {
            $this->terminalRaw(string: '<error>No migrations to rollback</error>');
            return 0;
        }

        // If no target version was supplied, revert the last migration
        if (null === $version) {
            // Get the migration before the last run migration
            $prev = count($versions) - 2;
            $version = $prev >= 0 ? $versions[$prev] : 0;
        } else {
            // Get the first migration number
            $first = reset($versions);

            // If the target version is before the first migration, revert all migrations
            if ($version < $first) {
                $version = 0;
            }
        }

        // Check the target version exists
        if (0 !== $version && !isset($migrations[$version])) {
            $this->terminalRaw(string: "<error>Target version ($version) not found</error>");
            return 0;
        }

        // Revert the migration(s)
        krsort($migrations);
        foreach ($migrations as $migration) {
            if ($migration->getVersion() <= $version) {
                break;
            }

            if (in_array(needle: $migration->getVersion(), haystack: $versions)) {
                $this->config->getConfigKey('database.phpmig.migrator')->down($migration);
            }
        }

        return ConsoleCommand::SUCCESS;
    }
}

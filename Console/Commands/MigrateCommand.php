<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;

class MigrateCommand extends PhpMigCommand
{
    protected string $name = 'migrate';

    protected string $description = 'Run all migrations.';

    protected string $help = <<<EOT
The <info>migrate</info> command runs all available migrations, optionally up to a specific version
<info>php codex migrate</info>
<info>php codex migrate -t 20111018185412</info>
EOT;

    protected array $options = [
        [
            '--target',
            '-t',
            'optional',
            'The version number to migrate to.',
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


        if (!empty($versions)) {
            // Get the last run migration number
            $current = end($versions);
        } else {
            $current = 0;
        }

        if (null !== $version) {
            if (0 !== $version && !isset($migrations[$version])) {
                return ConsoleCommand::SUCCESS;
            }
        } else {
            $versionNumbers = array_merge($versions, array_keys(array: $migrations));

            if (empty($versionNumbers)) {
                return ConsoleCommand::SUCCESS;
            }

            $version = max(value: $versionNumbers);
        }

        $direction = $version > $current ? 'up' : 'down';

        if ($direction === 'down') {
            /**
             * Run downs first
             */
            krsort($migrations);
            foreach ($migrations as $migration) {
                if ($migration->getVersion() <= $version) {
                    break;
                }

                if (in_array(needle: $migration->getVersion(), haystack: $versions)) {
                    $objectmap = $this->getObjectMap();
                    $objectmap['phpmig.migrator']->down($migration);
                }
            }
        }

        ksort($migrations);
        foreach ($migrations as $migration) {
            if ($migration->getVersion() > $version) {
                break;
            }

            if (!in_array(needle: $migration->getVersion(), haystack: $versions)) {
                $objectmap = $this->getObjectMap();
                $objectmap['phpmig.migrator']->up($migration);
            }
        }

        return ConsoleCommand::SUCCESS;
    }
}

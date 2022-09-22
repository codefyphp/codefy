<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;

class StatusCommand extends PhpMigCommand
{
    protected string $name = 'migrate:status';

    protected string $description = 'Show the up/down status of all migrations.';

    protected string $help = <<<EOT
The <info>migrate:status</info> command prints a list of all migrations, along with their current status 
<info>php codex migrate:status</info>
EOT;

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->bootstrap(input: $this->input, output: $this->output);

        $this->terminalRaw('');
        $this->terminalRaw(' Status   Migration ID    Migration Name ');
        $this->terminalRaw('-----------------------------------------');

        $versions = $this->getAdapter()->fetchAll();

        foreach ($this->getMigrations() as $migration) {
            if (in_array($migration->getVersion(), $versions)) {
                $status = '     <info>up</info> ';
                unset($versions[array_search($migration->getVersion(), $versions)]);
            } else {
                $status = '   <error>down</error> ';
            }

            $this->terminalRaw(
                $status .
                sprintf(" %14s ", $migration->getVersion()) .
                ' <comment>' . $migration->getName() . '</comment>'
            );
        }

        foreach ($versions as $missing) {
            $this->terminalRaw(
                '   <error>up</error> ' .
                sprintf(" %14s ", $missing) .
                ' <error>** MISSING **</error> '
            );
        }

        // print status
        $this->terminalRaw('');
        return ConsoleCommand::SUCCESS;
    }
}

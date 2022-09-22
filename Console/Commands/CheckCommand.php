<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Qubus\Exception\Exception;
use Symfony\Component\Console\Helper\Table;

class CheckCommand extends PhpMigCommand
{
    protected string $name = 'migrate:check';

    protected string $description = 'Check that all migrations have run; exit with non-zero if not.';

    protected string $help = <<<EOT
The <info>migrate:check</info> checks that all migrations have been run and exits with a 
non-zero exit code if not, useful for build or deployment scripts.
<info>php codex migrate:check</info>
EOT;

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->bootstrap(input: $this->input, output: $this->output);

        $versions = $this->getAdapter()->fetchAll();
        $down = [];

        foreach ($this->getMigrations() as $migration) {
            if (!in_array(needle: $migration->getVersion(), haystack: $versions)) {
                $down[] = $migration;
            }
        }

        if (!empty($down)) {
            /*$this->terminalRaw('');
            $this->terminalRaw(' Status   Migration ID    Migration Name ');
            $this->terminalRaw('-----------------------------------------');

            foreach ($down as $migration) {
                $this->terminalRaw(
                    sprintf(
                        '   <error>down</error>  %14s  <comment>%s</comment>',
                        $migration->getVersion(),
                        $migration->getName()
                    )
                );
            }

            $this->terminalRaw('');*/

            $table = new Table(output: $this->output);
            $table->setHeaders(headers: ['Status', 'Migration ID', 'Migration Name']);
            foreach ($down as $migration) {
                $table->addRow(
                    row: [
                        '<error>down</error>',
                        $migration->getVersion(),
                        "<comment>$migration->getName()</comment>"
                    ]
                );
            }
            $table->render();

            return 1;
        }

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Qubus\Exception\Exception;
use Symfony\Component\Console\Helper\Table;

class CheckCommand extends PhpMigCommand
{
    protected string $name = 'migrate:check';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription(description: 'Check that all migrations have run; exit with non-zero if not.')
            ->setHelp(
                help: <<<EOT
The <info>migrate:check</info> checks that all migrations have been run and exits with a 
non-zero exit code if not, useful for build or deployment scripts.
<info>php codex migrate:check</info>
EOT
            );
    }

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
            $table = new Table(output: $this->output);
            $table->setHeaders(headers: ['Status', 'Migration ID', 'Migration Name']);
            foreach ($down as $migration) {
                $table->addRow(
                    row: [
                        '<error>down</error>',
                        $migration->getVersion(),
                        "<comment>{$migration->getName()}</comment>"
                    ]
                );
            }

            $table->render();

            return 1;
        }

        $this->terminalInfo(string: 'No migrations to run.');

        return 0;
    }
}

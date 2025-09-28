<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Qubus\Exception\Exception;
use Symfony\Component\Console\Helper\Table;

use function sprintf;

class StatusCommand extends PhpMigCommand
{
    protected string $name = 'migrate:status';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription(description: 'Show the up/down status of all migrations.')
            ->setHelp(
                help: <<<EOT
The <info>migrate:status</info> command prints a list of all migrations, along with their current status 
<info>php codex migrate:status</info>
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

        $table = new Table(output: $this->output);
        $table->setHeaders(headers: ['Status', 'Migration ID', 'Migration Name']);

        foreach ($this->getMigrations() as $migration) {
            if (in_array($migration->getVersion(), $versions)) {
                $status = '<info>up</info>';
                unset($versions[array_search($migration->getVersion(), $versions)]);
            } else {
                $status = '<error>down</error>';
            }

            $table->addRow(
                row: [
                    $status,
                    sprintf(' %14s ', $migration->getVersion()),
                    "<comment>{$migration->getName()}</comment>"
                ]
            );
        }

        foreach ($versions as $missing) {
            $table->addRow(
                row: [
                    '<error>up</error>',
                    sprintf(' %14s ', $missing),
                    '<error>** MISSING **</error>'
                ]
            );
        }

        $table->render();

        // print status
        return ConsoleCommand::SUCCESS;
    }
}

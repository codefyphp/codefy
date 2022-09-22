<?php

declare(strict_types=1);

namespace Codefy\Foundation\Migration;

use Codefy\Foundation\Migration\Adapter\MigrationDatabaseAdapter;
use Symfony\Component\Console\Output\OutputInterface;

class Migrator
{
    /**
     * @var ?MigrationDatabaseAdapter
     */
    protected MigrationDatabaseAdapter|null $adapter = null;

    /**
     * @var ?OutputInterface
     */
    protected OutputInterface|null $output = null;

    /**
     * Constructor
     *
     * @param MigrationDatabaseAdapter $adapter
     */
    public function __construct(MigrationDatabaseAdapter $adapter, OutputInterface $output)
    {
        $this->adapter    = $adapter;
        $this->output     = $output;
    }

    /**
     * Run the up method on a migration
     *
     * @param Migration $migration
     * @return void
     */
    public function up(Migration $migration): void
    {
        $this->run(migration: $migration, direction: 'up');
    }

    /**
     * Run the down method on a migration
     *
     * @param Migration $migration
     * @return void
     */
    public function down(Migration $migration): void
    {
        $this->run(migration: $migration, direction: 'down');
    }

    /**
     * Run a migration in a particular direction
     *
     * @param Migration $migration
     * @param string $direction
     * @return void
     */
    protected function run(Migration $migration, string $direction = 'up'): void
    {
        $direction = ($direction == 'down' ? 'down' :'up');
        $this->getOutput()->writeln(
            messages: sprintf(
                ' == <info>' .
                $migration->getVersion() . ' ' .
                $migration->getName() . '</info> ' .
                '<comment>' .
                ($direction == 'up' ? 'migrating' : 'reverting') .
                '</comment>'
            )
        );
        $start = microtime(as_float: true);
        $migration->init();
        $migration->{$direction}();
        $this->getAdapter()->{$direction}($migration);
        $end = microtime(as_float: true);
        $this->getOutput()->writeln(
            messages: sprintf(
                ' == <info>' .
                $migration->getVersion() . ' ' .
                $migration->getName() . '</info> ' .
                '<comment>' .
                ($direction == 'up' ? 'migrated ' : 'reverted ') .
                sprintf("%.4fs", $end - $start) .
                '</comment>'
            )
        );
    }

    /**
     * Get Adapter
     *
     * @return MigrationDatabaseAdapter|null
     */
    public function getAdapter(): ?MigrationDatabaseAdapter
    {
        return $this->adapter;
    }

    /**
     * Set Adapter
     *
     * @param MigrationDatabaseAdapter $adapter
     * @return Migrator
     */
    public function setAdapter(MigrationDatabaseAdapter $adapter): static
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Get Output
     *
     * @return OutputInterface|null
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /**
     * Set Output
     *
     * @param OutputInterface $output
     * @return Migrator
     */
    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;
        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use PDO;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function sprintf;

final class MigrateFreshCommand extends PhpMigCommand
{
    protected string $name = 'migrate:fresh';

    protected function configure(): void
    {
        parent::configure();

        $this
                ->addOption(
                    name: 'force',
                    shortcut: '-f',
                    mode: InputOption::VALUE_NONE,
                    description: 'Run without confirmation (required in production).'
                )
                ->addOption(
                    name: 'seed',
                    mode: InputOption::VALUE_NONE,
                    description: 'Run database seeders after migration.'
                )
                ->addOption(
                    name: 'connection',
                    mode: InputOption::VALUE_REQUIRED,
                    description: 'Database connection name.'
                )
                ->setDescription(description: 'Drop all tables and re-run all migrations.')
                ->setHelp(
                    <<<EOT
The <info>migrate:fresh</info> command drops all database tables
and re-runs all migrations.

<info>php codex migrate:fresh</info>
<info>php codex migrate:fresh --seed</info>
<info>php codex migrate:fresh --force</info>
EOT
                );
    }

    public function handle(): int
    {
        $environment = $this->codefy->getEnvironment();
        $isProduction = $environment === 'production';

        if ($isProduction && ! $this->getOptions(key: 'force')) {
            $this->terminalError(
                string: 'Running migrate:fresh in production requires the --force option.'
            );
            return self::FAILURE;
        }

        if (! $this->getOptions(key: 'force')) {
            $this->terminalInfo(string: 'This will DROP ALL TABLES in the database.');

            if (! $this->confirm(question: 'Do you really want to continue?')) {
                return self::SUCCESS;
            }
        }

        try {
            $db = $this->codefy->getDbConnection();
            $pdo = $db->pdo;

            $this->terminalComment(string: 'Dropping all tables...');
            $this->dropAllTables($pdo);

            $this->terminalComment(string: 'Running migrations...');
            $this->call(command: 'migrate');

            if ($this->getOptions(key: 'seed')) {
                $this->terminalComment(string: 'Seeding database...');
                $this->call(command: 'db:seed', arguments: ['--force' => true]);
            }

            $this->terminalInfo(string: 'Database refreshed successfully.');
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->terminalError(string: 'Migration failed.');
            $this->terminalError($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->terminalComment($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Drop all tables using PDO (driver-aware)
     */
    private function dropAllTables(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(attribute: PDO::ATTR_DRIVER_NAME);

        match ($driver) {
            'mysql' => $this->dropMySqlTables($pdo),
            'pgsql' => $this->dropPostgresTables($pdo),
            'sqlite' => $this->dropSqliteTables($pdo),
            default => throw new RuntimeException(
                message: sprintf("Unsupported database driver: %s", $driver)
            ),
        };
    }

    private function dropMySqlTables(PDO $pdo): void
    {
        $tables = $pdo
            ->query(query: 'SHOW TABLES')
            ->fetchAll(mode: PDO::FETCH_COLUMN);

        if (empty($tables)) {
            return;
        }

        $pdo->exec(statement: 'SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $pdo->exec(statement: "DROP TABLE IF EXISTS `{$table}`");
        }

        $pdo->exec(statement: 'SET FOREIGN_KEY_CHECKS=1');
    }

    private function dropPostgresTables(PDO $pdo): void
    {
        $tables = $pdo
            ->query(
                query: "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
            )
            ->fetchAll(mode: PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec(statement: "DROP TABLE IF EXISTS \"{$table}\" CASCADE");
        }
    }

    private function dropSqliteTables(PDO $pdo): void
    {
        $tables = $pdo
            ->query(
                query: "SELECT name FROM sqlite_master WHERE type='table'"
            )
            ->fetchAll(mode: PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            if ($table !== 'sqlite_sequence') {
                $pdo->exec(statement: "DROP TABLE IF EXISTS \"{$table}\"");
            }
        }
    }
}

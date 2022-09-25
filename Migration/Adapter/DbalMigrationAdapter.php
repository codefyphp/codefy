<?php

declare(strict_types=1);

namespace Codefy\Foundation\Migration\Adapter;

use Codefy\Foundation\Migration\Migration;
use PDO;
use Qubus\Dbal\Connection;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use Qubus\Support\DateTime\QubusDateTimeImmutable;

use function array_map;
use function in_array;

class DbalMigrationAdapter implements MigrationAdapter
{
    public function __construct(protected Connection $connection, protected string $tableName)
    {
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get all migrated version numbers
     *
     * @return array
     */
    public function fetchAll(): array
    {
        $tableName = $this->connection->quoteIdentifier($this->tableName);
        $sql = $this->connection->getPdo()->prepare(query: "SELECT version FROM $tableName ORDER BY version ASC");
        $all = $sql->fetchAll(mode: PDO::FETCH_ASSOC);

        return array_map(fn ($v) => $v['version'], $all);
    }

    /**
     * Up
     *
     * @param Migration $migration
     * @return MigrationAdapter
     */
    public function up(Migration $migration): MigrationAdapter
    {
        $this->connection->insert($this->tableName)
            ->values(
                [
                    'version' => $migration->getVersion(),
                    'recorded_on' => (new QubusDateTimeImmutable('now'))->format('Y-m-d h:i:s')
                ]
            )->execute();

        return $this;
    }

    /**
     * Down
     *
     * @param Migration $migration
     * @return MigrationAdapter
     */
    public function down(Migration $migration): MigrationAdapter
    {
        $this->connection->delete($this->tableName)
            ->where('version', $migration->getVersion())
            ->execute();

        return $this;
    }

    /**
     * Is the schema ready?
     *
     * @return bool
     * @throws Exception
     */
    public function hasSchema(): bool
    {
        $tables = $this->connection->listTables();

        if (in_array(needle: $this->tableName, haystack: $tables)) {
            return true;
        }

        return false;
    }

    /**
     * Create Schema
     *
     * @return MigrationAdapter
     */
    public function createSchema(): MigrationAdapter
    {
        $this->connection->schema()->create($this->tableName, function (CreateTable $table) {
            $table->integer('id')->size('medium')->autoincrement();
            $table->string('version', 191)->notNull();
            $table->dateTime('recorded_on');
        });

        return $this;
    }
}

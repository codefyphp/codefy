<?php

declare(strict_types=1);

namespace Codefy\Foundation\Migration\Adapter;

use Codefy\Foundation\Migration\Migration;
use Qubus\Dbal\Connection;

interface MigrationDatabaseAdapter
{
    /**
     * Get all migrated version numbers
     *
     * @return array
     */
    public function fetchAll(): array;

    /**
     * Up
     *
     * @param Migration $migration
     * @return MigrationDatabaseAdapter
     */
    public function up(Migration $migration): MigrationDatabaseAdapter;

    /**
     * Down
     *
     * @param Migration $migration
     * @return MigrationDatabaseAdapter
     */
    public function down(Migration $migration): MigrationDatabaseAdapter;

    /**
     * Is the schema ready?
     *
     * @return bool
     */
    public function hasSchema(): bool;

    /**
     * Create Schema
     *
     * @return MigrationDatabaseAdapter
     */
    public function createSchema(): MigrationDatabaseAdapter;
}

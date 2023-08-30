<?php

declare(strict_types=1);

namespace Codefy\Framework\Migration\Adapter;

use Codefy\Framework\Migration\Migration;

interface MigrationAdapter
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
     * @return MigrationAdapter
     */
    public function up(Migration $migration): MigrationAdapter;

    /**
     * Down
     *
     * @param Migration $migration
     * @return MigrationAdapter
     */
    public function down(Migration $migration): MigrationAdapter;

    /**
     * Is the schema ready?
     *
     * @return bool
     */
    public function hasSchema(): bool;

    /**
     * Create Schema
     *
     * @return MigrationAdapter
     */
    public function createSchema(): MigrationAdapter;
}

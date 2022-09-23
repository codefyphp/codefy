<?php

declare(strict_types=1);

namespace Codefy\Foundation\Migration\Adapter;

use Codefy\Foundation\Migration\Migration;

use Qubus\Exception\Data\TypeException;

use function in_array;

class FileMigrationAdapter implements MigrationAdapter
{
    /**
     * @var string
     */
    protected string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(): array
    {
        $versions = file(filename: $this->filename, flags: FILE_IGNORE_NEW_LINES);
        sort($versions);
        return $versions;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration): MigrationAdapter
    {
        $versions = $this->fetchAll();
        if (in_array(needle: $migration->getVersion(), haystack: $versions)) {
            return $this;
        }

        $versions[] = $migration->getVersion();
        $this->write(versions: $versions);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration): MigrationAdapter
    {
        $versions = $this->fetchAll();
        if (!in_array(needle: $migration->getVersion(), haystack: $versions)) {
            return $this;
        }

        unset($versions[array_search(needle: $migration->getVersion(), haystack: $versions)]);
        $this->write(versions: $versions);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema(): bool
    {
        return file_exists(filename: $this->filename);
    }

    /**
     * {@inheritdoc}
     * @throws TypeException
     */
    public function createSchema(): MigrationAdapter
    {
        if (!is_writable(filename: dirname(path: $this->filename))) {
            throw new TypeException(message: sprintf('The file "%s" is not writeable', $this->filename));
        }

        if (false === touch(filename: $this->filename)) {
            throw new TypeException(message: sprintf('The file "%s" could not be written to', $this->filename));
        }

        return $this;
    }

    /**
     * Write to file
     *
     * @param array $versions
     * @throws TypeException
     */
    protected function write(array $versions)
    {
        if (false === file_put_contents(filename: $this->filename, data: implode(separator: "\n", array: $versions))) {
            throw new TypeException(message: sprintf('The file "%s" could not be written to', $this->filename));
        }
    }
}

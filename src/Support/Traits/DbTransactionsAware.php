<?php

declare(strict_types=1);

namespace Codefy\Framework\Support\Traits;

use PDO;

trait DbTransactionsAware
{
    protected bool $useTransaction = false;
    private ?PDO $transactionConnection = null;

    public function withTransaction(): static
    {
        $this->useTransaction = true;
        return $this;
    }

    protected function beginTransaction(): void
    {
        if (!$this->useTransaction) {
            return;
        }
        $connection = $this->container->make(PDO::class);
        if ($connection->inTransaction()) {
            throw new \LogicException('A transactional pipeline cannot own an existing transaction.');
        }
        if (!$connection->beginTransaction()) {
            throw new \RuntimeException('Unable to begin the pipeline transaction.');
        }
        $this->transactionConnection = $connection;
    }

    protected function commitTransaction(): void
    {
        if ($this->transactionConnection === null) {
            return;
        }
        if (!$this->transactionConnection->commit()) {
            throw new \RuntimeException('Unable to commit the pipeline transaction.');
        }
        $this->transactionConnection = null;
    }

    protected function rollbackTransaction(): void
    {
        $connection = $this->transactionConnection;
        $this->transactionConnection = null;
        if ($connection !== null && $connection->inTransaction()) {
            $connection->rollBack();
        }
    }
}

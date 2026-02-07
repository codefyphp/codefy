<?php

declare(strict_types=1);

namespace Codefy\Framework\Support\Traits;

use Codefy\Framework\Proxy\Codefy;

trait DbTransactionsAware
{
    //phpcs:disable
    /**
     * Determines whether class uses transaction.
     */
    protected bool $useTransaction = false {
        get => $this->useTransaction;
    }
    //phpcs:enable

    /**
     * Enable transaction in pipeline.
     */
    public function withTransaction(): static
    {
        $this->useTransaction = true;

        return $this;
    }

    /**
     * Begin the transaction if enabled.
     */
    protected function beginTransaction(): void
    {
        if (!$this->useTransaction) {
            return;
        }

        Codefy::$PHP->getDb()->beginTransaction();
    }

    /**
     * Commit the transaction if enabled.
     */
    protected function commitTransaction(): void
    {
        if (!$this->useTransaction) {
            return;
        }

        Codefy::$PHP->getDb()->commit();
    }

    /**
     * Rollback the transaction if enabled.
     */
    protected function rollbackTransaction(): void
    {
        if (!$this->useTransaction) {
            return;
        }

        Codefy::$PHP->getDb()->rollback();
    }
}

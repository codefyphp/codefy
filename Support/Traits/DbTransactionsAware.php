<?php

declare(strict_types=1);

namespace Codefy\Framework\Support\Traits;

use Codefy\Framework\Codefy;
use Qubus\Exception\Exception;

trait DbTransactionsAware
{
    /**
     * Determines whether class uses transaction.
     */
    protected bool $useTransaction = false;

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
     *
     * @throws Exception
     */
    protected function beginTransaction(): void
    {
        if (!$this->useTransaction) {
            return;
        }

        Codefy::$PHP->getDB()->beginTransaction();
    }

    /**
     * Commit the transaction if enabled.
     *
     * @throws Exception
     */
    protected function commitTransaction(): void
    {
        if (!$this->useTransaction) {
            return;
        }

        Codefy::$PHP->getDB()->commit();
    }

    /**
     * Rollback the transaction if enabled.
     *
     * @throws Exception
     */
    protected function rollbackTransaction(): void
    {
        if (!$this->useTransaction) {
            return;
        }

        Codefy::$PHP->getDB()->rollback();
    }
}

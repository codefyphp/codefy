<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Throttle;

use RuntimeException;

final class RateException extends RuntimeException
{
    private Condition $condition;

    /**
     * @param string $identifier
     * @param Condition $condition
     */
    public function __construct(string $identifier, Condition $condition)
    {
        $this->condition = $condition;
        parent::__construct(
            sprintf(
                'Rate %d in %d seconds was exceeded by "%s"',
                $condition->getLimit(),
                $condition->getTtl(),
                $identifier
            )
        );
    }

    /**
     * @return Condition|null
     */
    public function getCondition(): ?Condition
    {
        return $this->condition;
    }
}

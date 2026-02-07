<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Throttle;

class RateException extends \RuntimeException
{
    /**
     * @var Condition
     */
    public protected(set) Condition $condition {
        get {
            return $this->condition;
        }
    }

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
                $condition->limit,
                $condition->ttl,
                $identifier
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\ValueObject;

use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Ulid;

class TaskId extends Ulid
{
    /**
     * @throws TypeException
     */
    public static function fromString(?string $id = null): TaskId
    {
        return new self($id);
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth;

use Qubus\Http\Session\SessionEntity;

class UserSession implements SessionEntity
{
    public ?string $token = null;

    public ?string $role = null;

    public function withToken(?string $token = null): self
    {
        $this->token = $token;
        return $this;
    }

    public function withRole(?string $role = null): self
    {
        $this->role = $role;
        return $this;
    }

    public function clear(): void
    {
        if (!empty($this->token)) {
            unset($this->token);
        }

        if (!empty($this->role)) {
            unset($this->role);
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->token) && empty($this->role);
    }
}

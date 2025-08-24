<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf;

use function Qubus\Support\Helpers\is_null__;

class CsrfSession
{
    public ?string $csrfToken = null;

    public function withCsrfToken(?string $csrfToken = null): self
    {
        $this->csrfToken = $csrfToken;

        return $this;
    }

    public function equals(string $token): bool
    {
        return !is_null__($this->csrfToken) && $this->csrfToken === $token;
    }

    public function csrfToken(): string|null
    {
        return $this->csrfToken;
    }

    public function clear(): void
    {
        if (!empty($this->csrfToken)) {
            unset($this->csrfToken);
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->csrfToken);
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Auth;

use Codefy\Framework\Auth\Rbac\Entity\AssertionRule;

final class AuthorRule implements AssertionRule
{
    /**
     * @param array|null $params
     * @return bool
     */
    public function execute(?array $params = null): bool
    {
        if ($post = $params['post'] ?? null) {
            return $post->authorId === ($params['userId'] ?? null);
        }
        return false;
    }
}

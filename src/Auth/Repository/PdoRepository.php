<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Repository;

use Codefy\Framework\Support\Password;
use Opis\Database\Connection;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionEntity;
use SensitiveParameter;

use function sprintf;

class PdoRepository implements AuthUserRepository
{
    public function __construct(private Connection $connection, protected ConfigContainer $config)
    {
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function authenticate(string $credential, #[SensitiveParameter] ?string $password = null): ?SessionEntity
    {
        $fields = $this->config->getConfigKey(key: 'auth.pdo.fields');

        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = :identity",
            $this->config->getConfigKey('auth.pdo.table'),
            $fields['identity']
        );

        $stmt = $this->connection->getPDO()->prepare(query: $sql);
        if (false === $stmt) {
            return null;
        }

        $stmt->bindParam(':identity', $credential);
        $stmt->execute();

        $result = $stmt->fetchObject();
        if (! $result) {
            return null;
        }

        $passwordHash = (string) ($result->{$fields['password']} ?? '');

        if (Password::verify(password: $password ?? '', hash: $passwordHash)) {
            $user = new class () implements SessionEntity {
                public ?string $token = null;

                public function withToken(?string $token = null): self
                {
                    $this->token = $token;
                    return $this;
                }

                public function isEmpty(): bool
                {
                    return !empty($this->token);
                }
            };

            $user
                ->withToken($result->token);

            return $user;
        }

        return null;
    }
}

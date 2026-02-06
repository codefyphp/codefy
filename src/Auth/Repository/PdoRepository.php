<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Repository;

use Codefy\Framework\Auth\UserSession;
use Codefy\Framework\Support\Password;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Expressive\Connection;
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
        /** @var array<string> $fields */
        $fields = $this->config->getConfigKey(key: 'auth.pdo.fields');
        /** @var string $table */
        $table = $this->config->getConfigKey(key: 'auth.pdo.table');

        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = :identity",
            $table,
            $fields['identity']
        );

        $stmt = $this->connection->pdo->prepare($sql);
        if (false === $stmt) {
            return null;
        }

        $stmt->bindParam(':identity', $credential);
        $stmt->execute();

        /** @var false|null|object $result */
        $result = $stmt->fetchObject();
        if (! $result) {
            return null;
        }

        /** @var string $passwordHash */
        $passwordHash = $result->{$fields['password']} ?? '';

        if (Password::verify(password: $password ?? '', hash: $passwordHash)) {
            $user = new UserSession();
            $user
                ->withToken($result->token);

            return $user;
        }

        return null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function find(string $token): bool|null|object
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE token = :token",
            $this->config->getConfigKey('auth.pdo.table'),
        );

        $stmt = $this->connection->pdo->prepare($sql);
        if (false === $stmt) {
            return null;
        }

        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetchObject();
    }
}

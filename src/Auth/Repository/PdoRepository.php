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
        $fields = $this->config->getConfigKey(key: 'auth.pdo.fields');

        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = :identity",
            $this->config->getConfigKey('auth.pdo.table'),
            $fields['identity']
        );

        $stmt = $this->connection->pdo->prepare($sql);
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
            $user = new UserSession();
            $user
                ->withToken($result->token);

            return $user;
        }

        return null;
    }
}

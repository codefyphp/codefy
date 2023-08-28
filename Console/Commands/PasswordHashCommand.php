<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Support\Password;

class PasswordHashCommand extends ConsoleCommand
{
    protected string $name = 'password:hash';

    protected string $description = 'Hashes provided plain text password.';

    protected array $args = [
        ['password', 'required', 'Password to be hashed.']
    ];

    public function __construct(protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    public function handle(): int
    {
        $password = $this->getArgument(key: 'password');

        $hashedPassword = Password::hash(password: $password);

        $this->terminalRaw(string: sprintf(
            'Your hashed password is: <comment>%s</comment>',
            $hashedPassword
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

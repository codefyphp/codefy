<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console;

use Codefy\Foundation\Application;
use Codefy\Foundation\Support\Password;

class PasswordHashCommand extends ConsoleCommand
{
    protected string $name = 'password:hash';

    protected string $description = 'Hashes provided plain text password.';

    protected array $args = [
        ['password', 'required', 'Password to be hashed.']
    ];

    public function __construct(protected Application $codefy)
    {
        parent::__construct($codefy);
    }

    protected function handle(): int
    {
        $password = $this->getArgument('password');

        $hashedPassword = Password::hash($password);

        $this->terminalRaw(sprintf(
            'Your hashed password is: <comment>%s</comment>',
            $hashedPassword
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

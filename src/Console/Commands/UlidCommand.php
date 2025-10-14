<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Qubus\ValueObjects\Identity\Ulid;

class UlidCommand extends ConsoleCommand
{
    protected string $name = 'ddd:ulid';

    protected string $description = 'Generates a random Ulid string.';

    public function __construct(protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    public function handle(): int
    {
        $ulid = Ulid::generateAsString();

        $this->terminalRaw(string: sprintf(
            'Ulid: <comment>%s</comment>',
            $ulid
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

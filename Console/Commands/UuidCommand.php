<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Qubus\ValueObjects\Identity\Uuid;

class UuidCommand extends ConsoleCommand
{
    protected string $name = 'ddd:uuid';

    protected string $description = 'Generates a random Uuid string.';

    public function __construct(protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    public function handle(): int
    {
        $uuid = Uuid::generateAsString();

        $this->terminalRaw(string: sprintf(
            'Uuid: <comment>%s</comment>',
            $uuid
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

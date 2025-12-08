<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Support\Traits\CollectionStackAware;

class DefaultCommands
{
    use CollectionStackAware;

    public function __construct(array $collection = [])
    {
        $this->collection = $collection ?: [
            \Codefy\Framework\Console\Commands\MakeCommand::class,
            \Codefy\Framework\Console\Commands\Domain\MakeCommand::class,
            \Codefy\Framework\Console\Commands\Domain\MakeDomainCommand::class,
            \Codefy\Framework\Console\Commands\ScheduleRunCommand::class,
            \Codefy\Framework\Console\Commands\PasswordHashCommand::class,
            \Codefy\Framework\Console\Commands\InitCommand::class,
            \Codefy\Framework\Console\Commands\MigrateStatusCommand::class,
            \Codefy\Framework\Console\Commands\MigrateCheckCommand::class,
            \Codefy\Framework\Console\Commands\MigrateGenerateCommand::class,
            \Codefy\Framework\Console\Commands\MigrateUpCommand::class,
            \Codefy\Framework\Console\Commands\MigrateDownCommand::class,
            \Codefy\Framework\Console\Commands\MigrateCommand::class,
            \Codefy\Framework\Console\Commands\MigrateRollbackCommand::class,
            \Codefy\Framework\Console\Commands\MigrateRedoCommand::class,
            \Codefy\Framework\Console\Commands\ScheduleListCommand::class,
            \Codefy\Framework\Console\Commands\ServeCommand::class,
            \Codefy\Framework\Console\Commands\Domain\UuidCommand::class,
            \Codefy\Framework\Console\Commands\Domain\UlidCommand::class,
            \Codefy\Framework\Console\Commands\FlushPipelineCommand::class,
            \Codefy\Framework\Console\Commands\VendorPublishCommand::class,
            \Codefy\Framework\Console\Commands\GenerateEncryptionKeyCommand::class,
            \Codefy\Framework\Console\Commands\GenerateEncryptionKeyFileCommand::class,
            \Codefy\Framework\Console\Commands\EncryptEnvCommand::class,
            \Codefy\Framework\Console\Commands\QueueListCommand::class,
            \Codefy\Framework\Console\Commands\QueueRunCommand::class,
            \Codefy\Framework\Console\Commands\RouteListCommand::class,
        ];
    }
}

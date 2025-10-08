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
            \Codefy\Framework\Console\Commands\ScheduleRunCommand::class,
            \Codefy\Framework\Console\Commands\PasswordHashCommand::class,
            \Codefy\Framework\Console\Commands\InitCommand::class,
            \Codefy\Framework\Console\Commands\StatusCommand::class,
            \Codefy\Framework\Console\Commands\CheckCommand::class,
            \Codefy\Framework\Console\Commands\MigrateGenerateCommand::class,
            \Codefy\Framework\Console\Commands\UpCommand::class,
            \Codefy\Framework\Console\Commands\DownCommand::class,
            \Codefy\Framework\Console\Commands\MigrateCommand::class,
            \Codefy\Framework\Console\Commands\RollbackCommand::class,
            \Codefy\Framework\Console\Commands\RedoCommand::class,
            \Codefy\Framework\Console\Commands\ListCommand::class,
            \Codefy\Framework\Console\Commands\ServeCommand::class,
            \Codefy\Framework\Console\Commands\UuidCommand::class,
            \Codefy\Framework\Console\Commands\UlidCommand::class,
            \Codefy\Framework\Console\Commands\FlushPipelineCommand::class,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Qubus\Expressive\Migration\Seeder\SeederContext;
use Qubus\Expressive\Migration\Seeder\SeederTransaction;
use Symfony\Component\Console\Input\InputOption;

final class DatabaseSeedCommand extends ConsoleCommand
{
    protected string $name = 'db:seed';

    protected function configure(): void
    {
        parent::configure();

        $this
                ->addOption(
                    name: 'class',
                    shortcut: '-c',
                    mode: InputOption::VALUE_REQUIRED,
                    description: 'Seeder class to run (defaults to DatabaseSeeder).'
                )
                ->addOption(
                    name: 'force',
                    shortcut: '-f',
                    mode: InputOption::VALUE_NONE,
                    description: 'Run without confirmation (required in production).'
                )
                ->addOption(
                    name: 'env',
                    mode: InputOption::VALUE_REQUIRED,
                    description: 'Override application environment'
                )
                ->addOption(
                    name: 'faker-seed',
                    mode: InputOption::VALUE_REQUIRED,
                    description: 'Seed Faker for deterministic results.'
                )
                ->setDescription('Seed the database.')
                ->setHelp(
                    <<<EOT
The <info>db:seed</info> command seeds the database.

<info>php codex db:seed</info>
<info>php codex db:seed --class=UserSeeder</info>
<info>php codex db:seed --force</info>
EOT
                );
    }

    public function handle(): int
    {
        $environment = $this->getOptions(key: 'env') ?? $this->codefy->getEnvironment();
        $isProduction = $environment === 'production';

        $seederClass = $this->getOptions(key: 'class')
        ?? 'Database\\Seeders\\DatabaseSeeder';

        if ($isProduction && ! $this->getOptions(key: 'force')) {
            $this->terminalError(string: 'Seeding in production requires the --force option.');
            return self::FAILURE;
        }

        if (! $this->getOptions(key: 'force')) {
            $this->terminalInfo(string: sprintf(
                'You are about to seed the database in the <comment>%s</comment> environment.',
                $environment
            ));

            if (! $this->confirm(question: 'Do you wish to continue?')) {
                return self::SUCCESS;
            }
        }

        try {
            $context = new SeederContext(
                db: $this->codefy->getDb(),
                container: $this->codefy,
                environment: $environment,
                isProduction: $isProduction,
            );

            $transaction = new SeederTransaction($context);

            if ($fakerSeed = $this->getOptions(key: 'faker-seed')) {
                $this->terminalComment(string: "Using Faker seed: {$fakerSeed}");
                $this->codefy->alias(
                    original: 'seeder.faker_seed',
                    alias: $fakerSeed
                );
            }

            $this->terminalComment(string: "Seeding database using {$seederClass}...");
            $transaction->run($seederClass);

            $this->terminalInfo(string: 'Database seeding completed successfully.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->terminalError(string: 'Database seeding failed.');
            $this->terminalError($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->terminalComment($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}

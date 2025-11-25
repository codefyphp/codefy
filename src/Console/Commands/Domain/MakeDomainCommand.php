<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands\Domain;

use Codefy\Framework\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeDomainCommand extends ConsoleCommand
{
    protected string $name = 'ddd:make:domain';
    protected string $description = 'Initialize a complete Domain module directory structure.';

    /** -------------------------------------------
     *  Configure command signature
     * ------------------------------------------*/
    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'The name of the domain (e.g., Post, User, Order)'
        );
    }

    /** -------------------------------------------
     *  Execute command
     * ------------------------------------------*/
    protected function handle(): int
    {
        $domainName = trim($this->input->getArgument('name'));

        if ($domainName === '') {
            $this->output->writeln('<error>You must supply a domain name.</error>');
            return ConsoleCommand::FAILURE;
        }

        /** ------------------------------------------------------
         * 1. Resolve the base namespace that contains "Domain/"
         * ------------------------------------------------------*/
        $mappings = AutoloadResolver::getPsr4Mappings();
        $domainRootNamespace = null;
        $domainRootPath = null;

        foreach ($mappings as $namespace => $path) {
            // We treat ANY namespace with a "Domain" folder as valid
            $possibleDomainPath = $path . $this->codefy::DS . 'Domain';

            if (is_dir($possibleDomainPath)) {
                $domainRootNamespace = rtrim($namespace, '\\') . '\\Domain\\';
                $domainRootPath = $possibleDomainPath;
                break;
            }
        }

        if (!$domainRootNamespace || !$domainRootPath) {
            $this->output->writeln('<error>No Domain namespace found in PSR-4 mappings.</error>');
            $this->output->writeln('Make sure your project has something like:');
            $this->output->writeln('');
            $this->output->writeln('  "App\\\\": "App/",');
            $this->output->writeln('  "Domain directory exists: App/Domain"');
            return ConsoleCommand::FAILURE;
        }

        /** ------------------------------------------------------
         * 2. Build path to new Domain module
         * ------------------------------------------------------*/
        $modulePath = $domainRootPath . $this->codefy::DS . $domainName;

        /** subfolders we want to scaffold */
        $directories = [
            'Command',
            'Event',
            'Exception',
            'Query',
            'Repository',
            'Service',
            'ValueObject',
        ];

        /** ------------------------------------------------------
         * 3. Create the folders
         * ------------------------------------------------------*/
        foreach ($directories as $folder) {
            $dir = $modulePath . $this->codefy::DS . $folder;

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        /** ------------------------------------------------------
         * 4. Output results
         * ------------------------------------------------------*/
        $this->output->writeln("<info>Domain module created:</info> {$domainName}");
        $this->output->writeln('');
        $this->output->writeln('<comment>Directory structure:</comment>');

        $tree = <<<TXT
Domain
└── {$domainName}
    ├── Command
    ├── Event
    ├── Exception
    ├── Query
    ├── Repository
    ├── Service
    └── ValueObject
TXT;

        $this->output->writeln($tree);

        return ConsoleCommand::SUCCESS;
    }
}

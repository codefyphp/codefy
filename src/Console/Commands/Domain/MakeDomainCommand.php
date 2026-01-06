<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands\Domain;

use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Support\AutoloadResolver;
use Symfony\Component\Console\Input\InputArgument;

use function basename;
use function is_dir;
use function rtrim;

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
            return self::FAILURE;
        }

        $mappings = AutoloadResolver::getPsr4Mappings();

        if (empty($mappings)) {
            $this->terminalError('No PSR-4 namespaces found in your project composer.json.');
            return self::FAILURE;
        }

        $domainRootPath = null;

        foreach ($mappings as $namespace => $path) {
            $normalizedPath = rtrim($path, $this->codefy::DS);

            // CASE 1: The mapping path itself *is* the Domain root
            // e.g. "Codefy\\Domain\\": "src/Domain/"
            if (basename($normalizedPath) === 'Domain') {
                $domainRootPath = $normalizedPath;
                break;
            }

            // CASE 2: The Domain folder lives directly under the namespace root
            // e.g. "Codefy\\": "src/" and we have "src/Domain"
            $candidate = $normalizedPath . $this->codefy::DS . 'Domain';

            if (is_dir($candidate)) {
                $domainRootPath = $candidate;
                break;
            }
        }

        if ($domainRootPath === null) {
            $this->terminalError(
                'Unable to locate a Domain root. ' .
                'Expected either a "Domain" mapping (e.g. "Codefy\\\\Domain\\\\": "src/Domain/") ' .
                'or a Domain subdirectory under a PSR-4 root (e.g. "Codefy\\\\": "src/" with src/Domain).'
            );
            return self::FAILURE;
        }

        /** ------------------------------------------------------
         * 2. Build path to new Domain module
         * ------------------------------------------------------*/
        $modulePath = $domainRootPath . $this->codefy::DS . $domainName;

        /** subfolders we want to scaffold */
        $directories = [
            'Command',
            'Dto',
            'Enum',
            'Event',
            'Exception',
            'Query',
            'Repository',
            'Service',
            'Validator',
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
        $this->terminalInfo("Domain module '{$domainName}' created successfully.\n");

        $this->output->writeln("<comment>Directory structure:</comment>\n");
        $this->output->writeln("Domain");
        $this->output->writeln("└── {$domainName}");
        $this->output->writeln("    ├── Command");
        $this->output->writeln("    ├── Dto");
        $this->output->writeln("    ├── Enum");
        $this->output->writeln("    ├── Event");
        $this->output->writeln("    ├── Exception");
        $this->output->writeln("    ├── Query");
        $this->output->writeln("    ├── Repository");
        $this->output->writeln("    ├── Service");
        $this->output->writeln("    ├── Validator");
        $this->output->writeln("    └── ValueObject");

        return self::SUCCESS;
    }
}

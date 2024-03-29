<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;

use function Codefy\Framework\Helpers\public_path;

class ServeCommand extends ConsoleCommand
{
    protected string $name = 'serve';

    protected array $options = [
        [
            'php',
            null,
            'optional',
            'The PHP binary [default: "PHP_BINARY"].',
            false
        ],
        [
            'host',
            null,
            'optional',
            'Server HTTP host [default: "localhost"].',
            false
        ],
        [
            'port',
            null,
            'optional',
            'The HTTP host port [default: "8080"].',
            false
        ],
    ];

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription(description: 'Launches the CodefyPHP development server.')
            ->setHelp(
                help: <<<EOT
The <info>serve</info> command to start the development server
<info>php codex serve</info>
EOT
            );
    }

    public function handle(): int
    {
        $php = escapeshellarg(empty($this->getOptions(key: 'php')) ? PHP_BINARY : $this->getOptions(key: 'php'));
        $host = empty($this->getOptions(key: 'host')) ? 'localhost' : $this->getOptions(key: 'host');
        $port = empty($this->getOptions(key: 'port')) ? '8080' : $this->getOptions(key: 'port');

        $this->terminalComment(string: sprintf('CodefyPHP development server started on http://%s:%s', $host, $port));
        $this->terminalComment(string: 'Press control-C to stop.');

        $docroot = escapeshellarg(public_path());

        passthru($php . ' -S ' . $host . ':' . $port . ' -t ' . $docroot);

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

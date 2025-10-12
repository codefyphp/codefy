<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Qubus\NoSql\Exceptions\InvalidJsonException;
use Qubus\NoSql\Node;
use Qubus\Support\Serializer\JsonSerializer;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;

use function Codefy\Framework\Helpers\database_path;
use function Codefy\Framework\Helpers\queue;

class QueueRunCommand extends ConsoleCommand
{
    protected string $name = 'queue:run';

    protected string $description = 'Executes queues that are due.';

    protected string $help = <<<EOT
The <info>queue:run</info> command dispatches the queues that are due to run.
<info>php codex queue:run</info>
EOT;

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(
                name: 'name',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Runs the queue based on given node.'
            );
    }

    public function handle(): int
    {
        try {
            $path = "" !== $this->getOptions('name') ? $this->getOptions('name') : 'nodequeue';

            $db = Node::open(database_path(path: $path));
            $queues = $db->all();

            foreach ($queues as $queue) {
                $object = new JsonSerializer()->unserialize($queue['object']);
                queue($object)->dispatch();
            }
        } catch (InvalidJsonException | ReflectionException $e) {
            return ConsoleCommand::FAILURE;
        }

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

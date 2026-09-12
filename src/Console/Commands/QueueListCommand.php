<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Cron\CronExpression;
use Qubus\NoSql\Node;
use Codefy\Framework\Queue\JobSerializer;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;

use function Codefy\Framework\Helpers\database_path;
use function get_class;

class QueueListCommand extends ConsoleCommand
{
    protected string $name = 'queue:list';

    protected string $description = 'Lists the existing queued jobs.';

    protected string $help = <<<EOT
The <info>queue:list</info> command prints a table of available queues.
<info>php codex queue:list</info>
EOT;

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(
                name: 'name',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'List the queued jobs by node.'
            );
    }

    public function handle(): int
    {
        try {
            $table = new Table($this->output);
            $table->setHeaders([
                "Queue Name",
                "Class",
                "Expression",
                "Next Execution",
                "Attempts",
                "State",
            ]);

            $path = $this->getOptions('name') ?: 'nodequeue';

            $db = Node::open(database_path(path: $path));
            $queues = $db->all();

            foreach ($queues as $queue) {
                $object = JobSerializer::decode(
                    $queue['object'],
                    $this->codefy->configContainer->getConfigKey('queue.jobs', [])
                );

                $nextRun = CronExpression::isValidExpression($object->schedule)
                ? new CronExpression($object->schedule)->getNextRunDate()
                : \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $object->schedule);
                if ($nextRun === false) {
                    throw new \UnexpectedValueException('Invalid queue schedule.');
                }

                $table->addRow([
                    $queue['name'],
                    get_class($object),
                    $object->schedule,
                    $nextRun->format(format: 'd F Y h:i A'),
                    $queue['executions'],
                    ($queue['failed'] ?? false) ? 'failed' : ($queue['expire'] > time() ? 'leased' : 'pending'),
                ]);
            }

            $table->render();
        } catch (\Throwable $e) {
            return ConsoleCommand::FAILURE;
        }

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use League\Flysystem\FilesystemException;
use Qubus\Exception\Exception;
use Qubus\FileSystem\FileSystem;
use Symfony\Component\Console\Input\InputOption;

use function Codefy\Framework\Helpers\public_path;

class FlushPipelineCommand extends ConsoleCommand
{
    protected string $name = 'asset:flush';

    protected function configure(): void
    {
        parent::configure();

        $this
                ->addOption(
                    name: 'group',
                    shortcut: '-g',
                    mode: InputOption::VALUE_REQUIRED,
                    description: 'Only flush the provided group'
                )
                ->addOption(
                    name: 'force',
                    shortcut: '-f',
                    mode: InputOption::VALUE_NONE,
                    description: 'Do not prompt for confirmation'
                )
                ->setDescription(description: 'Flush assets pipeline..')
                ->setHelp(
                    help: <<<EOT
The <info>asset:flush</info> flushes the assets pipeline.
<info>php codex asset:flush</info>
EOT
                );
    }

    /**
     * @throws Exception
     * @throws FilesystemException
     */
    public function handle(): int
    {
        // Get directories to purge
        if (! $directories = $this->getPipelineDirectories()) {
            $this->terminalError(string: 'The provided group does not exist');

            return ConsoleCommand::FAILURE;
        }

        // Ask for confirmation
        if (! $this->getOptions(key: 'force')) {
            $this->terminalInfo(string: 'All content of the following directories will be deleted:');

            foreach ($directories as $dir) {
                $this->terminalComment($dir);
            }

            if (! $this->confirm(question: 'Do you want to continue?')) {
                return ConsoleCommand::SUCCESS;
            }
        }

        // Purge directories
        $this->terminalComment(string: 'Flushing pipeline directories...');

        foreach ($directories as $dir) {
            $this->output->write(messages: "$dir ");

            if ($this->purgeDir($dir)) {
                $this->terminalInfo(string: 'OK');
            } else {
                $this->terminalError(string: 'ERROR');
            }
        }

        $this->terminalComment(string: 'Done!');

        return ConsoleCommand::SUCCESS;
    }

    /**
     * Get the pipeline directories of the groups.
     *
     * @return array
     * @throws Exception
     */
    protected function getPipelineDirectories(): array
    {
        // Parse configured groups
        $config = $this->codefy->configContainer->getConfigKey(key: 'assets');

        $groups = (isset($config['default'])) ? $config : ['default' => $config];

        if (! empty($group = $this->getOptions(key: 'group'))) {
            $groups = $this->codefy->array->subset(array: $groups, keys: [$group]);
        }

        // Parse pipeline directories of each group
        $directories = [];

        foreach ($groups as $group => $config) {
            $pipelineDir = (isset($config['pipeline_dir'])) ? $config['pipeline_dir'] : 'min';
            $publicDir = (isset($config['public_dir'])) ? $config['public_dir'] : public_path();
            $publicDir = rtrim($publicDir, $this->codefy::DS);

            $cssDir = (isset($config['css_dir'])) ? $config['css_dir'] : 'css';
            $directories[] = implode($this->codefy::DS, [$publicDir, $cssDir, $pipelineDir]);

            $jsDir = (isset($config['js_dir'])) ? $config['js_dir'] : 'js';
            $directories[] = implode($this->codefy::DS, [$publicDir, $jsDir, $pipelineDir]);
        }

        // Clean results
        $directories = array_unique($directories);
        sort($directories);

        return $directories;
    }

    /**
     * Remove the contents of a given directory.
     *
     * @param string $directory
     * @return bool
     * @throws FilesystemException
     */
    protected function purgeDir(string $directory): bool
    {
        /** @var FileSystem $filesystem */
        $filesystem = $this->codefy->make(name: 'filesystem.default');

        if (! $filesystem->directoryExists($directory)) {
            return true;
        }

        if (\Qubus\Support\Helpers\is_writable($directory)) {
            $contents = $filesystem->listContents(location: $directory, deep: true)->toArray();

            foreach ($contents as $item) {
                // Check if the item is a file or a directory
                if ($item->isFile()) {
                    $filesystem->delete($item->path());
                } elseif ($item->isDir()) {
                    // Delete the directory and its contents (already handled by recursive listing)
                    // You might want to delete directories only after their contents are cleared
                    // Or, if you're deleting from the deepest level first, this will work.
                    // For a more controlled approach, you might want to sort the $contents
                    // to process deeper items first.
                    $filesystem->deleteDirectory($item->path());
                }
            }
            $filesystem->deleteDirectory($directory);

            return true;
        }

        $this->terminalError(sprintf('Directory "%s" is not writable.', $directory));

        return false;
    }
}

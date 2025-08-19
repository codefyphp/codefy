<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Kernel
{
    /**
     * Handle an incoming console command.
     *
     * @param InputInterface $input
     * @param OutputInterface|null $output
     * @return int
     */
    public function handle(InputInterface $input, ?OutputInterface $output = null): int;

    /**
     * Gets all the commands registered.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string;

    /**
     * Bootstrap the console kernel.
     *
     * @return void
     */
    public function bootstrap(): void;

    /**
     * Run a Codex console command by name.
     *
     * @param string $command
     * @param array $parameters
     * @param bool|OutputInterface|null $outputBuffer
     * @return int
     */
    public function call(string $command, array $parameters = [], bool|OutputInterface $outputBuffer = null): int;
}

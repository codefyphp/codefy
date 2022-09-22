<?php

declare(strict_types=1);

namespace Codefy\Foundation\Migration;

use Codefy\Foundation\Application;
use Codefy\Foundation\Migration\Adapter\MigrationDatabaseAdapter;
use Qubus\Dbal\Schema;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class Migration
{
    protected ?int $version = null;

    protected ?InputInterface $input = null;

    protected ?OutputInterface $output = null;

    protected ?QuestionHelper $dialogHelper = null;

    protected ?MigrationDatabaseAdapter $adapter = null;

    /**
     * Constructor
     *
     * @param int $version
     */
    final public function __construct(int $version, protected Application $codefy)
    {
        $this->version = $version;
        $this->adapter = $this->codefy->make(name: 'codefy.config')->getConfigKey('database.phpmig.adapter');
    }

    /**
     * init
     *
     * @return void
     */
    public function init(): void
    {
        return;
    }

    /**
     * Do the migration
     *
     * @return void
     */
    public function up(): void
    {
        return;
    }

    /**
     * Undo the migration
     *
     * @return void
     */
    public function down(): void
    {
        return;
    }

    /**
     * Get adapter.
     *
     * @return MigrationDatabaseAdapter
     */
    public function getAdapter(): MigrationDatabaseAdapter
    {
        return $this->adapter;
    }

    /**
     * Set adapter.
     *
     * @param MigrationDatabaseAdapter $adapter
     * @return Migration
     */
    public function setAdapter(MigrationDatabaseAdapter $adapter): static
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Get Version
     *
     * @return int|null
     */
    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * Set version
     *
     * @param int $version
     * @return Migration
     */
    public function setVersion(int $version): static
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return get_class(object: $this);
    }

    /**
     * Get Output
     *
     * @return OutputInterface|null
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /**
     * Set Output
     *
     * @param OutputInterface $output
     * @return Migration
     */
    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Get Input
     *
     * @return InputInterface|null
     */
    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    /**
     * Set Input
     *
     * @param InputInterface $input
     * @return Migration
     */
    public function setInput(InputInterface $input): static
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Ask for input
     *
     * @param Question $question
     * @return mixed
     */
    public function ask(Question $question): string
    {
        return $this->getDialogHelper()->ask(input: $this->getInput(), output: $this->getOutput(), question: $question);
    }

    /**
     * Get Dialog Helper
     *
     * @return QuestionHelper|null
     */
    public function getDialogHelper(): ?QuestionHelper
    {
        if ($this->dialogHelper) {
            return $this->dialogHelper;
        }

        return $this->dialogHelper = new QuestionHelper();
    }

    /**
     * Set Dialog Helper
     *
     * @param QuestionHelper $dialogHelper
     * @return Migration
     */
    public function setDialogHelper(QuestionHelper $dialogHelper): static
    {
        $this->dialogHelper = $dialogHelper;
        return $this;
    }

    public function schema(): Schema
    {
        return $this->getAdapter()->connection()->schema();
    }
}

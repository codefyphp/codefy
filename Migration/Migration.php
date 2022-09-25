<?php

declare(strict_types=1);

namespace Codefy\Foundation\Migration;

use ArrayAccess;
use Codefy\Foundation\Migration\Adapter\MigrationAdapter;
use Qubus\Dbal\Schema;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

use function get_class;

class Migration
{
    protected int|string|null $version = null;

    protected ?ArrayAccess $objectmap = null;

    protected ?InputInterface $input = null;

    protected ?OutputInterface $output = null;

    protected ?QuestionHelper $dialogHelper = null;

    protected ?MigrationAdapter $adapter = null;

    /**
     * Constructor
     *
     * @param int|string $version
     */
    final public function __construct(int|string $version)
    {
        $this->version = $version;
    }

    /**
     * Init.
     *
     * @return void
     */
    public function init(): void
    {
        return;
    }

    /**
     * Do the migration.
     *
     * @return void
     */
    public function up(): void
    {
        return;
    }

    /**
     * Undo the migration.
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
     * @return MigrationAdapter
     */
    public function getAdapter(): MigrationAdapter
    {
        return $this->get('phpmig.adapter');
    }

    /**
     * Get Version.
     *
     * @return int|string|null
     */
    public function getVersion(): int|string|null
    {
        return $this->version;
    }

    /**
     * Set version.
     *
     * @param int|string $version
     * @return Migration
     */
    public function setVersion(int|string $version): static
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return get_class(object: $this);
    }

    /**
     * Get ObjectMap.
     *
     * @return ArrayAccess
     */
    public function getObjectMap(): ArrayAccess
    {
        return $this->objectmap;
    }

    /**
     * Set ObjectMap.
     *
     * @param ArrayAccess $objectmap
     * @return Migration
     */
    public function setObjectMap(ArrayAccess $objectmap): static
    {
        $this->objectmap = $objectmap;
        return $this;
    }


    /**
     * Get Output.
     *
     * @return OutputInterface|null
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /**
     * Set Output.
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
     * Get Input.
     *
     * @return InputInterface|null
     */
    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    /**
     * Set Input.
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
     * Ask for input.
     *
     * @param Question $question
     * @return mixed
     */
    public function ask(Question $question): string
    {
        return $this->getDialogHelper()->ask(input: $this->getInput(), output: $this->getOutput(), question: $question);
    }

    /**
     * Get something from the objectmap
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $c = $this->getObjectMap();
        return $c[$key];
    }

    /**
     * Get Dialog Helper.
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
     * Set Dialog Helper.
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

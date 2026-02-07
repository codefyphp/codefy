<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Request;

use Codefy\Framework\Proxy\Codefy;
use Codefy\Framework\Traits\InputValidationAware;
use Codefy\Framework\Validation\DataValidator;
use Qubus\Http\ServerRequest;
use Qubus\Injector\ServiceContainer;
use Qubus\Support\DataType;
use Qubus\Validation\ErrorBag;
use Qubus\Validation\Factories\ValidationFactory;
use Qubus\Validation\Validation;

use function array_merge;
use function method_exists;

abstract class FormRequest extends ServerRequest implements DataValidator
{
    use InputValidationAware;

    /**
     * The filtered input data.
     *
     * @param array<mixed> $data
     */
    protected array $data = [];

    //phpcs:disable
    protected ?ServiceContainer $container = null
    {
        get => $this->container ?? Codefy::$PHP;
        set(null|ServiceContainer $value) => $this->container  = $value;
    }
    //phpcs:enable

    /**
     * The URI to redirect to if validation fails.
     */
    protected ?string $redirectUri = null;

    /**
     * The validator instance.
     */
    protected ?Validation $validator = null;

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        if (empty($this->data)) {
            $this->data = array_merge(
                $this->getQueryParams(),
                (array) $this->getParsedBody(),
                $this->getUploadedFiles()
            );
        }

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function only(array $keys): static
    {
        $clone = clone $this;
        $clone->data = new DataType()->array->only($this->all(), $keys);
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function except(array $keys): static
    {
        $clone = clone $this;
        $clone->data = new DataType()->array->except($this->all(), $keys);
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function validated(): array
    {
        $this->validateResolved();

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function value(mixed $value, mixed $default = null): mixed
    {
        return $this->validated()[$value] ?? $default;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string|null>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Get the validator instance for the request.
     *
     * @throws \Exception
     */
    protected function makeValidator(): ?Validation
    {
        if ($this->validator) {
            return $this->validator;
        }

        $factory = $this->container->make(name: ValidationFactory::class);

        if (!method_exists($this, 'validator')) {
            $validator = $this->createDefaultValidator($factory);
            $this->setValidator($validator);
        }

        return $this->validator;
    }

    /**
     * Create the default validator instance.
     *
     * @param ValidationFactory $factory
     * @return Validation
     * @throws \Exception
     */
    protected function createDefaultValidator(ValidationFactory $factory): Validation
    {
        $rules = $this->validationRules();

        return $factory->make(
            $this->all(),
            $rules,
            $this->messages()
        );
    }

    /**
     * Set the Validator instance.
     *
     * @param Validation $validator
     * @return static
     */
    public function setValidator(Validation $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the container implementation.
     *
     * @param ServiceContainer $container
     * @return static
     */
    public function setContainer(ServiceContainer $container): static
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function errors(): ErrorBag
    {
        return $this->validator->errors();
    }

    /**
     * Get the validation rules for this form request.
     *
     * @return array<string|null>
     */
    protected function validationRules(): array
    {
        return method_exists(object_or_class: $this, method: 'rules')
        ? $this->rules()
        : [];
    }
}

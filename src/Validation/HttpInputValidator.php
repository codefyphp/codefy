<?php

declare(strict_types=1);

namespace Codefy\Framework\Validation;

use Codefy\Framework\Proxy\Codefy;
use Codefy\Framework\Traits\InputValidationAware;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Injector\ServiceContainer;
use Qubus\Support\DataType;
use Qubus\Validation\ErrorBag;
use Qubus\Validation\Factories\ValidationFactory;
use Qubus\Validation\Validation;

use function array_merge;
use function method_exists;

abstract class HttpInputValidator implements DataValidator
{
    use InputValidationAware;

    /**
     * The filtered input data.
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

    final private function __construct(protected ServerRequestInterface $request)
    {
    }

    /**
     * Factory.
     *
     * @param ServerRequestInterface $request
     * @return self
     */
    public static function make(ServerRequestInterface $request): static
    {
        return new static($request);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        if (empty($this->data)) {
            $this->data = array_merge(
                $this->request->getQueryParams(),
                (array) $this->request->getParsedBody(),
                $this->request->getUploadedFiles()
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
     * @return array
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Get the validator instance for the request.
     *
     * @throws Exception
     */
    protected function makeValidator(): Validation
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
     * @throws Exception
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
     * @return $this
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
     * @return $this
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
     * @return array
     */
    protected function validationRules(): array
    {
        return method_exists(object_or_class: $this, method: 'rules')
        ? $this->rules()
        : [];
    }
}

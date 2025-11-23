<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Request;

use Exception;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Validation\Factories\ValidationFactory;
use Qubus\Validation\Validation;

use function array_merge;
use function is_array;

abstract class FormRequest implements FormRequestMiddleware
{
    private ?Validation $validator = null;

    public function __construct(
        protected readonly ValidationFactory $validatorFactory,
        private readonly FormRequestErrorResponder $errorResponder
    ) {
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authorize($request)) {
            return $this->errorResponder->unauthorized();
        }

        $this->validator = $this->makeValidator($request);
        if ($this->fails($request)) {
            return $this->errorResponder->validationFailed($this->errors());
        }

        return $handler->handle($request);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function fails(ServerRequestInterface $request): bool
    {
        $this->validator = $this->makeValidator($request);
        return $this->validator->fails();
    }

    /**
     * @inheritdoc
     */
    public function errors(): array
    {
        if ($this->validator === null) {
            throw new LogicException(
                'Execute \Codefy\Framework\Http\Middleware\Request\FormRequest::fails() method before getting errors.'
            );
        }

        return $this->validator->errors()->toArray();
    }

    /**
     * @inheritdoc
     */
    public function authorize(ServerRequestInterface $request): bool
    {
        return false;
    }

    /**
     * validation rules
     * @return array
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * validation messages
     * @return array
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * @param ServerRequestInterface $request
     * @return Validation
     * @throws Exception
     */
    private function makeValidator(ServerRequestInterface $request): Validation
    {
        $query = $request->getQueryParams();
        $body = $request->getParsedBody();
        $inputs = is_array($body) ? array_merge($query, $body) : $query;

        $validator = $this->validatorFactory->make($inputs, $this->rules(), $this->messages());
        $validator->validate();

        return $validator;
    }
}
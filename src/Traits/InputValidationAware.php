<?php

declare(strict_types=1);

namespace Codefy\Framework\Traits;

use Codefy\Framework\Auth\Rbac\Exception\UnauthorizedException;
use Qubus\Validation\Validation;
use Qubus\Validation\ValidationException;

use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

trait InputValidationAware
{
    /**
     * Validate the class instance.
     *
     * @return void
     * @throws UnauthorizedException
     * @throws \Exception
     */
    public function validateResolved(): void
    {
        $this->prepareForValidation();

        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $instance = $this->getValidatorInstance();
        $instance->validate();

        if ($instance->fails()) {
            $this->failedValidation();
        }

        $this->passedValidation();
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        //
    }

    /**
     * Get the validator instance for the request.
     *
     * @return Validation
     * @throws \Exception
     */
    protected function getValidatorInstance(): Validation
    {
        return $this->makeValidator();
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation()
    {
        //
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * @return bool
     */
    protected function passesAuthorization(): bool
    {
        if (method_exists(object_or_class: $this, method: 'authorize')) {
            return $this->authorize();
        }

        return true;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     * @throws \JsonException
     */
    protected function failedValidation(?string $message = null, int $code = 422): void
    {
        throw new ValidationException(
            uri: $this->redirectUri,
            message: $message ?? sprintf(
                'Validation failed: %s',
                json_encode(value: $this->errors()->firstOfAll(), flags: JSON_THROW_ON_ERROR)
            ),
            code: $code
        );
    }

    /**
     * @throws UnauthorizedException
     */
    protected function failedAuthorization(): void
    {
        throw new UnauthorizedException(
            uri: $this->redirectUri,
            message: 'This action is unauthorized.',
            code: 401
        );
    }
}

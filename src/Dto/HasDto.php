<?php

declare(strict_types=1);

namespace Codefy\Framework\Dto;

interface HasDto
{
    /**
     * Convert validated data to DTO.
     *
     * @return object The corresponding DTO instance.
     */
    public function toDto(): object;

    /**
     * Get the DTO class name for this Input Validator or Form Request.
     *
     * @return string The fully qualified DTO class name.
     */
    public function getDtoClass(): string;
}

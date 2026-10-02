<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use RuntimeException;
use Throwable;

/**
 * @property-read string $code Código de domínio em formato textual (ex: VALIDATION_ERROR).
 */
class CoffeeMailException extends RuntimeException
{
    public readonly int $status;
    public readonly string $errorCode;
    public readonly mixed $details;

    public function __construct(
        string $message,
        int $status = 500,
        string $errorCode = 'INTERNAL_ERROR',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'code') {
            return $this->errorCode;
        }

        return null;
    }

    public function __isset(string $name): bool
    {
        return $name === 'code';
    }
}

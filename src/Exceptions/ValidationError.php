<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class ValidationError extends CoffeeMailException
{
    public function __construct(
        string $message,
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 400,
            errorCode: 'VALIDATION_ERROR',
            details: $details,
            previous: $previous,
        );
    }
}

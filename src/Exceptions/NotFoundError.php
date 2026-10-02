<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class NotFoundError extends CoffeeMailException
{
    public function __construct(
        string $message = 'Recurso não encontrado.',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 404,
            errorCode: 'NOT_FOUND',
            details: $details,
            previous: $previous,
        );
    }
}

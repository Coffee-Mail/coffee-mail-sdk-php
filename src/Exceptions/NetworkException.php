<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class NetworkException extends CoffeeMailException
{
    public function __construct(
        string $message = 'Falha de comunicação ou timeout na rede.',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 0,
            errorCode: 'NETWORK_ERROR',
            details: $details,
            previous: $previous,
        );
    }
}

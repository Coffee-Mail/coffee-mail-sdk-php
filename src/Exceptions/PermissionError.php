<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

/**
 * A chave de API não tem a permissão exigida pela operação (hoje, `full_access` em campanhas).
 */
final class PermissionError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message,
        public readonly string $requiredPermission = 'full_access',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 403,
            errorCode: ErrorCodes::PERMISSION_DENIED,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}

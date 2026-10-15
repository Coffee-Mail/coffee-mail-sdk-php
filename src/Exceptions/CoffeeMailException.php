<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Erro base de todas as operações do SDK.
 *
 * `errorCode` é o código canônico, igual em todos os SDKs oficiais (ver {@see ErrorCodes}).
 * `apiCode` é o código original enviado pela API (ex.: PLAN_QUOTA_EXCEEDED).
 *
 * @property-read string $code Código canônico (alias de `errorCode`).
 */
class CoffeeMailException extends RuntimeException
{
    public readonly int $status;
    public readonly string $errorCode;

    /** @var array<string, mixed> */
    public readonly array $details;

    public readonly ?string $apiCode;
    public readonly ?string $requestId;

    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message,
        int $status = 0,
        string $errorCode = ErrorCodes::API_ERROR,
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct($message, $status, $previous);
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->details = $details;
        $this->apiCode = $apiCode;
        $this->requestId = $requestId;
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

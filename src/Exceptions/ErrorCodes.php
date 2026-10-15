<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

/**
 * Códigos canônicos de erro expostos em `CoffeeMailException::$errorCode`.
 */
final class ErrorCodes
{
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const PAYMENT_REQUIRED = 'PAYMENT_REQUIRED';
    public const FORBIDDEN = 'FORBIDDEN';
    public const NOT_FOUND = 'NOT_FOUND';
    public const CONFLICT = 'CONFLICT';
    public const RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    public const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    public const NETWORK_ERROR = 'NETWORK_ERROR';
    public const PERMISSION_DENIED = 'PERMISSION_DENIED';
    public const API_ERROR = 'API_ERROR';
    public const INVALID_RESPONSE = 'INVALID_RESPONSE';

    private function __construct()
    {
    }
}

<?php

declare(strict_types=1);

namespace CoffeeMail\Tests\Support;

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\ResponseParser;

final class DomainFixtures
{
    public const DOMAIN_ID = '7f0c1d2e-3a4b-4c5d-8e6f-7a8b9c0d1e2f';
    public const TRACKING_TARGET = 'track.coffeemail.com.br';
    public const VERIFIED_AT = '2026-10-15T12:00:00.000Z';

    /**
     * @param array<string, mixed>|null $data
     */
    public static function transport(?array $data): FakeTransport
    {
        return new FakeTransport(defaultResponse: new CoffeeMailResponse(data: $data, error: null, statusCode: 200));
    }

    public static function client(FakeTransport $transport): CoffeeMail
    {
        return new CoffeeMail('cm_live_dummy', transport: $transport);
    }

    public static function failing(int $status, string $code): CoffeeMail
    {
        $body = json_encode(['error' => ['code' => $code, 'message' => $code]], JSON_THROW_ON_ERROR);

        return self::client(new FakeTransport(defaultResponse: ResponseParser::parse($status, $body, [], 'pt-BR')));
    }

    /**
     * @return array<string, mixed>
     */
    public static function customHost(string $host, bool $verified): array
    {
        return [
            'host' => $host,
            'target' => self::TRACKING_TARGET,
            'verified' => $verified,
            'verifiedAt' => $verified ? self::VERIFIED_AT : null,
            'record' => ['type' => 'CNAME', 'host' => $host, 'value' => self::TRACKING_TARGET],
        ];
    }
}

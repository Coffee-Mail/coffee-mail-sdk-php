<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

/**
 * Contatos de uma audiência (`$client->audiences->contacts`).
 */
final class Contacts
{
    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
    }

    /**
     * Adiciona um contato à audiência (`email`, `firstName`, `lastName`, `metadata`).
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(string $audienceId, array $payload): CoffeeMailResponse
    {
        return $this->http->post(self::path($audienceId), $payload);
    }

    /**
     * Lista contatos da audiência, paginados por `limit`/`offset`.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(string $audienceId, array $query = []): CoffeeMailResponse
    {
        return $this->http->get(self::path($audienceId), $query);
    }

    /**
     * Importa até 10 000 contatos de uma vez.
     *
     * @param list<array<string, mixed>> $contacts
     * @return CoffeeMailResponse<mixed>
     */
    public function bulkAdd(string $audienceId, array $contacts): CoffeeMailResponse
    {
        return $this->http->post(self::path($audienceId) . '/bulk', ['contacts' => $contacts]);
    }

    /**
     * Altera um contato (`firstName`, `lastName`, `metadata`).
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function update(string $audienceId, string $contactId, array $payload): CoffeeMailResponse
    {
        return $this->http->put(self::path($audienceId) . '/' . rawurlencode($contactId), $payload);
    }

    /**
     * Remove um contato da audiência.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $audienceId, string $contactId): CoffeeMailResponse
    {
        return $this->http->delete(self::path($audienceId) . '/' . rawurlencode($contactId));
    }

    private static function path(string $audienceId): string
    {
        return '/v1/product/audiences/' . rawurlencode($audienceId) . '/contacts';
    }
}

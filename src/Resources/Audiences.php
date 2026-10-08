<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final readonly class Audiences
{
    public function __construct(
        private HttpTransportInterface $http,
    ) {
    }

    /**
     * Cria uma nova audiência (lista de contatos).
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/audiences', $payload);
    }

    /**
     * Lista as audiências cadastradas na organização.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/audiences', $query);
    }

    /**
     * Obtém os detalhes de uma audiência pelo ID.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/audiences/' . rawurlencode($id));
    }

    /**
     * Exclui uma audiência e todos os seus contatos associados.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $id): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/audiences/' . rawurlencode($id));
    }

    /**
     * Lista contatos de uma audiência de forma paginada.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function listContacts(string $audienceId, array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/audiences/' . rawurlencode($audienceId) . '/contacts', $query);
    }

    /**
     * Adiciona um contato individual a uma audiência.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function addContact(string $audienceId, array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/audiences/' . rawurlencode($audienceId) . '/contacts', $payload);
    }

    /**
     * Adiciona múltiplos contatos a uma audiência em lote (bulk).
     *
     * @param list<array<string, mixed>> $contacts
     * @return CoffeeMailResponse<mixed>
     */
    public function bulkAddContacts(string $audienceId, array $contacts): CoffeeMailResponse
    {
        /** @var list<mixed> $rawList */
        $rawList = $contacts;

        return $this->http->post('/v1/product/audiences/' . rawurlencode($audienceId) . '/contacts/bulk', $rawList);
    }

    /**
     * Remove um contato de uma audiência.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function removeContact(string $audienceId, string $contactId): CoffeeMailResponse
    {
        return $this->http->delete(
            '/v1/product/audiences/' . rawurlencode($audienceId) . '/contacts/' . rawurlencode($contactId),
        );
    }
}

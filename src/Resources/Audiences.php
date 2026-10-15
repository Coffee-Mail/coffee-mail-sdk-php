<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final class Audiences
{
    public readonly Contacts $contacts;

    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
        $this->contacts = new Contacts($http);
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
     * Altera nome, descrição ou estado de uma audiência.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function update(string $id, array $payload): CoffeeMailResponse
    {
        return $this->http->put('/v1/product/audiences/' . rawurlencode($id), $payload);
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
     * Atalho para `contacts->list()`.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function listContacts(string $audienceId, array $query = []): CoffeeMailResponse
    {
        return $this->contacts->list($audienceId, $query);
    }

    /**
     * Atalho para `contacts->create()`.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function createContact(string $audienceId, array $payload): CoffeeMailResponse
    {
        return $this->contacts->create($audienceId, $payload);
    }

    /**
     * Atalho para `contacts->bulkAdd()`.
     *
     * @param list<array<string, mixed>> $contacts
     * @return CoffeeMailResponse<mixed>
     */
    public function bulkAddContacts(string $audienceId, array $contacts): CoffeeMailResponse
    {
        return $this->contacts->bulkAdd($audienceId, $contacts);
    }
}

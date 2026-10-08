<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\Payloads\DomainPayload;

final readonly class Domains
{
    public function __construct(
        private HttpTransportInterface $http,
    ) {}

    /**
     * Cadastra um novo domínio na organização para envio de e-mails.
     *
     * @param array<string, mixed>|DomainPayload $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array|DomainPayload $payload): CoffeeMailResponse
    {
        $data = $payload instanceof DomainPayload ? $payload->toArray() : $payload;

        return $this->http->post('/v1/product/domains', $data);
    }

    /**
     * Lista todos os domínios registrados pela organização.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/domains', $query);
    }

    /**
     * Obtém os detalhes e status de verificação de um domínio pelo ID.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/domains/' . rawurlencode($id));
    }

    /**
     * Solicita verificação forçada dos registros DNS (SPF, DKIM, DMARC, MX) do domínio.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function verify(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/domains/' . rawurlencode($id) . '/verify');
    }

    /**
     * Exclui um domínio da organização.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $id): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/domains/' . rawurlencode($id));
    }
}

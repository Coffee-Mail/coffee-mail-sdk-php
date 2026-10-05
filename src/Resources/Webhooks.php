<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final readonly class Webhooks
{
    public function __construct(
        private HttpTransportInterface $http,
    ) {}

    /**
     * Cadastra um novo endpoint de webhook na plataforma.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/webhooks', $payload);
    }

    /**
     * Lista todos os webhooks cadastrados na organização.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/webhooks', $query);
    }

    /**
     * Obtém detalhes de um webhook cadastrado.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/webhooks/' . rawurlencode($id));
    }

    /**
     * Atualiza configurações de um webhook existente.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function update(string $id, array $payload): CoffeeMailResponse
    {
        return $this->http->patch('/v1/product/webhooks/' . rawurlencode($id), $payload);
    }

    /**
     * Remove permanentemente um endpoint de webhook.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $id): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/webhooks/' . rawurlencode($id));
    }

    /**
     * Ativa ou desativa temporariamente o envio de eventos para um webhook.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function toggle(string $id, bool $enabled): CoffeeMailResponse
    {
        return $this->http->patch('/v1/product/webhooks/' . rawurlencode($id) . '/toggle', ['enabled' => $enabled]);
    }

    /**
     * Rotaciona o segredo HMAC de um webhook.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function rotateSecret(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/webhooks/' . rawurlencode($id) . '/rotate-secret');
    }

    /**
     * Dispara um envio de teste simulado para o endpoint do webhook.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function test(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/webhooks/' . rawurlencode($id) . '/test');
    }

    /**
     * Consulta o histórico de tentativas de entrega de eventos para o webhook.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function listDeliveries(string $id, array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/webhooks/' . rawurlencode($id) . '/deliveries', $query);
    }

    /**
     * Valida a autenticidade de um webhook recebido via HMAC SHA-256.
     * Protege contra requisições forjadas e ataques de temporização (timing attacks).
     */
    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        if ($payload === '' || $signature === '' || $secret === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }
}

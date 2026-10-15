<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\Payloads\WebhookPayload;
use CoffeeMail\Webhooks\WebhookVerifier;

final class Webhooks
{
    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
    }

    /**
     * Cadastra um novo endpoint de webhook na plataforma.
     *
     * @param array<string, mixed>|WebhookPayload $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array|WebhookPayload $payload): CoffeeMailResponse
    {
        $data = $payload instanceof WebhookPayload ? $payload->toArray() : $payload;

        return $this->http->post('/v1/product/webhooks', $data);
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
     * Atualiza configurações de um webhook existente (PUT com campos parciais).
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function update(string $id, array $payload): CoffeeMailResponse
    {
        return $this->http->put('/v1/product/webhooks/' . rawurlencode($id), $payload);
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
        return $this->http->patch('/v1/product/webhooks/' . rawurlencode($id), [
            'status' => $enabled ? 'active' : 'paused',
        ]);
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
     * Verifica a assinatura de uma entrega de webhook (algoritmo da API: `sha256=` +
     * HMAC-SHA256 de `timestamp.idempotencyKey.corpoBruto`, janela de 300s).
     *
     * Passe os cabeçalhos da requisição em `$headers` ou os valores explícitos.
     *
     * @param array<array-key, mixed> $headers
     */
    public static function verifySignature(
        string $payload,
        string $secret,
        array $headers = [],
        ?string $signature = null,
        ?string $timestamp = null,
        ?string $idempotencyKey = null,
        int $toleranceSeconds = WebhookVerifier::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): bool {
        return WebhookVerifier::verify(
            $payload,
            $secret,
            $headers,
            $signature,
            $timestamp,
            $idempotencyKey,
            $toleranceSeconds,
            $now,
        );
    }
}

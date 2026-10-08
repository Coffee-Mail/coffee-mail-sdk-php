<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final class Templates
{
    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
    }

    /**
     * Cria um novo modelo de e-mail na organização.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/templates', $payload);
    }

    /**
     * Lista os modelos de e-mail cadastrados.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/templates', $query);
    }

    /**
     * Obtém os detalhes e conteúdo de um modelo pelo ID.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/templates/' . rawurlencode($id));
    }

    /**
     * Atualiza os dados ou conteúdo HTML de um modelo.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function update(string $id, array $payload): CoffeeMailResponse
    {
        return $this->http->patch('/v1/product/templates/' . rawurlencode($id), $payload);
    }

    /**
     * Remove permanentemente um modelo de e-mail.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $id): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/templates/' . rawurlencode($id));
    }

    /**
     * Renderiza uma prévia do modelo substituindo as variáveis informadas.
     *
     * @param array<string, mixed> $variables
     * @return CoffeeMailResponse<mixed>
     */
    public function preview(string $id, array $variables = []): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/templates/' . rawurlencode($id) . '/preview', [
            'variables' => $variables,
        ]);
    }
}

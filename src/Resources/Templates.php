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
     * Renderiza uma prévia a partir do código-fonte informado, sem persistir nada.
     *
     * @param array<string, mixed> $payload Aceita html, format e variables.
     * @return CoffeeMailResponse<mixed>
     */
    public function preview(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/templates/preview', $payload);
    }

    /**
     * Renderiza a prévia de um modelo já salvo, a partir do seu identificador.
     *
     * @param array<string, mixed> $variables
     * @return CoffeeMailResponse<mixed>
     */
    public function previewById(string $id, array $variables = []): CoffeeMailResponse
    {
        $template = $this->get($id);
        if ($template->error !== null) {
            return $template;
        }

        $data = is_array($template->data) ? $template->data : [];

        return $this->preview([
            'html' => $data['html'] ?? '',
            'format' => $data['format'] ?? 'html',
            'variables' => $variables,
        ]);
    }

    /**
     * Formata o código-fonte de um template via Prettier, sem persistir nada.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function format(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/templates/format', $payload);
    }

    /**
     * Renderiza um template e devolve o relatório de sanitização, sem persistir.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function testRender(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/templates/test-render', $payload);
    }

    /**
     * Dispara um envio de teste de um template já salvo.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function testSend(string $id, array $payload): CoffeeMailResponse
    {
        return $this->http->post(
            '/v1/product/templates/' . rawurlencode($id) . '/test-send',
            $payload
        );
    }

    /**
     * Lista o histórico de versões de um modelo, da mais recente para a mais antiga.
     *
     * Cada item traz `version`, `name`, `subject`, `createdBy` e `createdAt`; a página
     * vem em `{ data, total }`.
     *
     * @param array<string, mixed> $query Aceita `limit` (1 a 100) e `offset`.
     * @return CoffeeMailResponse<mixed>
     */
    public function listVersions(string $id, array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/templates/' . rawurlencode($id) . '/versions', $query);
    }

    /**
     * Obtém uma versão completa de um modelo (`html`, `contentJson`, `variables`, `format` etc.).
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function getVersion(string $id, int $version): CoffeeMailResponse
    {
        return $this->http->get($this->versionPath($id, $version));
    }

    /**
     * Restaura o conteúdo de uma versão anterior e devolve o modelo atualizado.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function restoreVersion(string $id, int $version): CoffeeMailResponse
    {
        return $this->http->post($this->versionPath($id, $version) . '/restore');
    }

    private function versionPath(string $id, int $version): string
    {
        return '/v1/product/templates/' . rawurlencode($id) . '/versions/' . $version;
    }
}

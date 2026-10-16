<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\Payloads\DomainPayload;

final class Domains
{
    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
    }

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
     * Além dos registros DNS, traz `dkimRotation` (rotação de DKIM pendente ou `null`),
     * `returnPath` e `trackingDomain` (`{host, target, verified, verifiedAt, record}` ou `null`)
     * e `trackingDomainsAvailable` (se o plano permite domínio de rastreamento próprio).
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

    /**
     * Executa a checagem de saúde DNS de um domínio.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function getHealth(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/domains/' . rawurlencode($id) . '/health');
    }

    /**
     * Consulta o progresso do aquecimento de IP do domínio.
     *
     * `data` é `null` quando o domínio não tem aquecimento. Quando existe, traz `domainId`,
     * `status` (`warming` ou `completed`), `currentDay`, `totalDays`, `dailyQuota`, `sentToday`,
     * `remainingToday`, `quotaUsedPercent`, `lastSendDate`, `startedAt` e `completedAt`
     * (ISO 8601 ou `null` enquanto aquece).
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function getWarmupStatus(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/domains/' . rawurlencode($id) . '/warmup');
    }

    /**
     * Inicia a rotação da chave DKIM do domínio verificado.
     *
     * Idempotente: com uma rotação pendente, devolve a mesma. A resposta traz `domainId`,
     * `selector`, `startedAt` e `record` (`type`, `host`, `value`, `ttl`, `isInfrastructure`),
     * o registro a publicar no DNS. A chave em uso só muda quando esse registro propaga.
     * Erros: 404 (`DOMAIN_NOT_FOUND`) e 409 (`DOMAIN_NOT_VERIFIED`).
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function rotateDkim(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/domains/' . rawurlencode($id) . '/dkim/rotate');
    }

    /**
     * Define o subdomínio próprio usado nos links de abertura e clique.
     *
     * Devolve `{host, target, verified, verifiedAt, record}`: publique `record` (CNAME para
     * `target`) no DNS. Erros: 400 (`INVALID_TRACKING_HOST`), 404 e 409
     * (`TRACKING_DOMAINS_DISABLED`, recurso indisponível no plano).
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function setTrackingDomain(string $id, string $host): CoffeeMailResponse
    {
        return $this->http->put(
            '/v1/product/domains/' . rawurlencode($id) . '/tracking-domain',
            ['host' => $host],
        );
    }

    /**
     * Remove o domínio de rastreamento próprio; os links voltam ao domínio padrão.
     *
     * `data` traz o status do host restante ou `null`.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function removeTrackingDomain(string $id): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/domains/' . rawurlencode($id) . '/tracking-domain');
    }
}

<?php

declare(strict_types=1);

namespace CoffeeMail;

use CoffeeMail\Exceptions\AuthenticationError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\CurlTransport;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\I18n\I18n;

class CoffeeMail
{
    private const INTROSPECTION_CACHE_TTL_SECONDS = 300;

    private readonly string $apiKey;
    private readonly HttpTransportInterface $transport;

    public readonly Resources\Emails $emails;
    public readonly Resources\Webhooks $webhooks;
    public readonly Resources\Domains $domains;
    public readonly Resources\Templates $templates;
    public readonly Resources\Audiences $audiences;
    public readonly Resources\Broadcasts $broadcasts;
    public readonly Resources\Senders $senders;

    public readonly Resources\Suppressions $suppressions;
    public readonly Resources\Stats $stats;

    /** @var mixed */
    private mixed $introspectionCache = null;
    private int $introspectionCacheExpiresAt = 0;

    public function __construct(
        ?string $apiKey = null,
        string $locale = 'pt-BR',
        int $timeoutSeconds = 10,
        ?HttpTransportInterface $transport = null,
    ) {
        $resolvedKey = $apiKey;

        if ($resolvedKey === null || trim($resolvedKey) === '') {
            $envKey = getenv('COFFEEMAIL_API_KEY');
            if (is_string($envKey) && trim($envKey) !== '') {
                $resolvedKey = trim($envKey);
            }
        }

        if ($resolvedKey === null || trim($resolvedKey) === '') {
            throw new AuthenticationError(I18n::getMessage('missing_api_key', $locale));
        }

        $this->apiKey = trim($resolvedKey);
        $this->transport = $transport ?? new CurlTransport(
            apiKey: $this->apiKey,
            locale: $locale,
            timeoutSeconds: $timeoutSeconds,
        );

        $this->emails = new Resources\Emails($this->transport);
        $this->webhooks = new Resources\Webhooks($this->transport);
        $this->domains = new Resources\Domains($this->transport);
        $this->templates = new Resources\Templates($this->transport);
        $this->audiences = new Resources\Audiences($this->transport);
        $this->broadcasts = new Resources\Broadcasts($this->transport);
        $this->senders = new Resources\Senders($this->transport);
        $this->suppressions = new Resources\Suppressions($this->transport);
        $this->stats = new Resources\Stats($this->transport);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getLocale(): string
    {
        return $this->transport->getLocale();
    }

    public function getTransport(): HttpTransportInterface
    {
        return $this->transport;
    }

    /**
     * Consulta detalhes da chave de API em uso, com cache local de 5 minutos.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function introspect(bool $forceRefresh = false): CoffeeMailResponse
    {
        $now = time();

        if (! $forceRefresh && $this->introspectionCache !== null && $now < $this->introspectionCacheExpiresAt) {
            return new CoffeeMailResponse(
                data: $this->introspectionCache,
                error: null,
                statusCode: 200,
            );
        }

        $response = $this->transport->get('/v1/product/auth/me/api-key');

        if ($response->isSuccess()) {
            $this->introspectionCache = $response->data;
            $this->introspectionCacheExpiresAt = $now + self::INTROSPECTION_CACHE_TTL_SECONDS;
        }

        return $response;
    }

    /**
     * Invalida o cache local de introspecção da chave de API.
     */
    public function invalidateApiKeyCache(): void
    {
        $this->introspectionCache = null;
        $this->introspectionCacheExpiresAt = 0;
    }
}

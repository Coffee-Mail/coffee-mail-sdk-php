<?php

declare(strict_types=1);

namespace CoffeeMail\I18n;

final class I18n
{
    public const DEFAULT_LOCALE = 'pt-BR';
    public const SUPPORTED_LOCALES = ['pt-BR', 'en'];

    /**
     * @var array<string, array<string, string>>
     */
    private const MESSAGES = [
        'missing_api_key' => [
            'pt-BR' => 'Chave de API não informada. '
                . 'Forneça a chave no construtor ou defina a variável COFFEEMAIL_API_KEY.',
            'en' => 'API key was not provided. '
                . 'Please pass a valid API key to the constructor or set COFFEEMAIL_API_KEY.',
        ],
        'timeout_error' => [
            'pt-BR' => 'A requisição excedeu o tempo limite configurado de {timeoutSeconds}s.',
            'en' => 'The request timed out after {timeoutSeconds}s.',
        ],
        'network_error' => [
            'pt-BR' => 'Falha de comunicação de rede com os servidores do CoffeeMail: {details}',
            'en' => 'Network communication failure with CoffeeMail servers: {details}',
        ],
        'curl_init_failed' => [
            'pt-BR' => 'Não foi possível inicializar a extensão cURL.',
            'en' => 'Failed to initialize the cURL extension.',
        ],
        'unexpected_error' => [
            'pt-BR' => 'Ocorreu um erro inesperado durante a comunicação com a API do CoffeeMail.',
            'en' => 'An unexpected error occurred while communicating with CoffeeMail API.',
        ],
        'invalid_webhook_signature' => [
            'pt-BR' => 'Assinatura do webhook inválida ou não confere com o segredo configurado.',
            'en' => 'Invalid webhook signature or secret mismatch.',
        ],
        'permission_denied' => [
            'pt-BR' => 'Esta chave de API não possui a permissão necessária ({requiredPermission}) para executar esta'
                . " operação. Crie uma chave com permissão 'full_access' no dashboard.",
            'en' => 'This API key lacks the required permission ({requiredPermission}) for this operation.'
                . " Create a key with 'full_access' permission in the dashboard.",
        ],
        'invalid_json' => [
            'pt-BR' => 'A resposta do servidor não pôde ser interpretada como JSON válido.',
            'en' => 'The server response could not be parsed as valid JSON.',
        ],
        'rate_limit_exceeded' => [
            'pt-BR' => 'Limite de requisições excedido (Rate Limit). Tente novamente em alguns segundos.',
            'en' => 'Rate limit exceeded. Please retry in a few moments.',
        ],
    ];

    /**
     * @param array<string, string|int|float> $replacements
     */
    public static function getMessage(
        string $key,
        string $locale = self::DEFAULT_LOCALE,
        array $replacements = [],
    ): string {
        $normalizedLocale = in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : self::DEFAULT_LOCALE;
        $entry = self::MESSAGES[$key] ?? null;

        if ($entry === null) {
            return $key;
        }

        $text = $entry[$normalizedLocale];

        foreach ($replacements as $placeholder => $value) {
            $text = str_replace('{' . $placeholder . '}', (string) $value, $text);
        }

        return $text;
    }
}

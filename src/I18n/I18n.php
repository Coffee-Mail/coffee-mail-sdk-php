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
            'pt-BR' => 'Esta chave de API não possui permissão para executar esta operação.',
            'en' => 'This API key lacks the required permission for this operation.',
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

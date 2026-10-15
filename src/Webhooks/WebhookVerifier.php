<?php

declare(strict_types=1);

namespace CoffeeMail\Webhooks;

/**
 * Verifica a autenticidade de entregas de webhook da CoffeeMail.
 *
 * A assinatura é `sha256=` seguido do HMAC-SHA256 em hexadecimal de
 * `timestamp + "." + idempotencyKey + "." + corpoBruto`, comparada em tempo constante.
 * Entregas com timestamp fora da janela de tolerância (padrão de 5 minutos) são
 * rejeitadas para impedir replay.
 */
final class WebhookVerifier
{
    public const HEADER_SIGNATURE = 'x-webhook-signature';
    public const HEADER_TIMESTAMP = 'x-webhook-timestamp';
    public const HEADER_IDEMPOTENCY_KEY = 'x-webhook-idempotency-key';
    public const HEADER_TEST = 'x-webhook-test';
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    private const SIGNATURE_PREFIX = 'sha256=';
    private const SEPARATOR = '.';
    private const ALGORITHM = 'sha256';
    private const TIMESTAMP_PATTERN = '/^-?\d{1,18}$/';

    private function __construct()
    {
    }

    /**
     * Verifica uma entrega. Os valores explícitos têm precedência sobre os cabeçalhos.
     *
     * @param string $payload corpo bruto, exatamente como recebido (nunca re-serialize o JSON)
     * @param array<array-key, mixed> $headers cabeçalhos da requisição (nomes em qualquer caixa;
     *     valores string ou lista de strings, como em `$request->headers->all()`)
     * @param int|null $now relógio em segundos Unix, útil em testes; padrão `time()`
     */
    public static function verify(
        string $payload,
        string $secret,
        array $headers = [],
        ?string $signature = null,
        ?string $timestamp = null,
        ?string $idempotencyKey = null,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): bool {
        $resolvedSignature = $signature ?? self::header($headers, self::HEADER_SIGNATURE);
        $resolvedTimestamp = trim($timestamp ?? self::header($headers, self::HEADER_TIMESTAMP) ?? '');
        $resolvedKey = $idempotencyKey ?? self::header($headers, self::HEADER_IDEMPOTENCY_KEY);

        if ($secret === '' || $resolvedSignature === null || $resolvedKey === null) {
            return false;
        }

        if (!self::withinTolerance($resolvedTimestamp, $toleranceSeconds, $now ?? time())) {
            return false;
        }

        return hash_equals(self::sign($payload, $secret, $resolvedTimestamp, $resolvedKey), $resolvedSignature);
    }

    /**
     * Calcula a assinatura esperada de uma entrega. Útil para testar o seu endpoint.
     */
    public static function sign(string $payload, string $secret, string $timestamp, string $idempotencyKey): string
    {
        $content = $timestamp . self::SEPARATOR . $idempotencyKey . self::SEPARATOR . $payload;

        return self::SIGNATURE_PREFIX . hash_hmac(self::ALGORITHM, $content, $secret);
    }

    private static function withinTolerance(string $timestamp, int $toleranceSeconds, int $now): bool
    {
        if (preg_match(self::TIMESTAMP_PATTERN, $timestamp) !== 1) {
            return false;
        }

        return abs($now - (int) $timestamp) <= $toleranceSeconds;
    }

    /**
     * @param array<array-key, mixed> $headers
     */
    private static function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) !== $name) {
                continue;
            }

            $first = is_array($value) ? reset($value) : $value;

            return is_string($first) && $first !== '' ? $first : null;
        }

        return null;
    }
}

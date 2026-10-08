<?php

declare(strict_types=1);

namespace CoffeeMail\Payloads;

use InvalidArgumentException;

final class AttachmentPayload implements PayloadInterface
{
    public function __construct(
        public readonly string $filename,
        public readonly string $content,
        public readonly ?string $contentType = null,
    ) {
    }

    /**
     * Cria uma instância de anexo a partir de conteúdo em bytes brutos ou string em disco.
     */
    public static function fromPath(string $filePath, ?string $filename = null, ?string $contentType = null): self
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("Arquivo de anexo não encontrado ou inacessível: {$filePath}");
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent === false) {
            throw new InvalidArgumentException("Falha ao ler o conteúdo do arquivo: {$filePath}");
        }

        $resolvedFilename = $filename ?? basename($filePath);
        $resolvedContentType = $contentType;

        if ($resolvedContentType === null && function_exists('mime_content_type')) {
            $detected = mime_content_type($filePath);
            if (is_string($detected)) {
                $resolvedContentType = $detected;
            }
        }

        return new self(
            filename: $resolvedFilename,
            content: base64_encode($rawContent),
            contentType: $resolvedContentType,
        );
    }

    /**
     * Cria anexo a partir de string de dados brutos (raw bytes).
     */
    public static function fromRaw(string $filename, string $rawBytes, ?string $contentType = null): self
    {
        return new self(
            filename: $filename,
            content: base64_encode($rawBytes),
            contentType: $contentType,
        );
    }

    /**
     * Cria anexo com string já codificada em Base64.
     */
    public static function fromBase64(string $filename, string $base64Content, ?string $contentType = null): self
    {
        return new self(
            filename: $filename,
            content: $base64Content,
            contentType: $contentType,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'filename' => $this->filename,
            'content' => $this->content,
        ];

        if ($this->contentType !== null) {
            $data['contentType'] = $this->contentType;
        }

        return $data;
    }
}

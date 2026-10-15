<?php

declare(strict_types=1);

namespace CoffeeMail;

/**
 * Versão deste SDK, enviada no cabeçalho User-Agent (`coffeemail-php/<versão>`).
 */
final class Version
{
    public const VALUE = '0.2.0';

    private function __construct()
    {
    }
}

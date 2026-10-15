<?php

declare(strict_types=1);

/**
 * Gate de versão: CoffeeMail\Version::VALUE precisa ter seção no CHANGELOG.md e,
 * quando rodando a partir de uma tag (GITHUB_REF_NAME=vX.Y.Z), bater com a tag.
 */

$root = dirname(__DIR__);
$versionFile = file_get_contents($root . '/src/Version.php');
$changelog = file_get_contents($root . '/CHANGELOG.md');

if ($versionFile === false || $changelog === false) {
    fwrite(STDERR, '[check-version] src/Version.php ou CHANGELOG.md ausente.' . PHP_EOL);
    exit(1);
}

if (preg_match("/public const VALUE = '([^']+)';/", $versionFile, $match) !== 1) {
    fwrite(STDERR, '[check-version] constante Version::VALUE não encontrada.' . PHP_EOL);
    exit(1);
}

$version = $match[1];
$errors = [];

if (preg_match('/^## \[' . preg_quote($version, '/') . '\]/m', $changelog) !== 1) {
    $errors[] = sprintf('CHANGELOG.md sem a seção [%s].', $version);
}

$tag = getenv('GITHUB_REF_NAME');
if (is_string($tag) && str_starts_with($tag, 'v') && $tag !== 'v' . $version) {
    $errors[] = sprintf('a tag %s não corresponde a Version::VALUE=%s.', $tag, $version);
}

if ($errors === []) {
    echo '[check-version] versão ' . $version . ' sincronizada.' . PHP_EOL;
    exit(0);
}

foreach ($errors as $error) {
    fwrite(STDERR, '[check-version] ' . $error . PHP_EOL);
}
exit(1);

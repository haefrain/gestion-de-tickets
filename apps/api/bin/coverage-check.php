<?php

declare(strict_types=1);

// Gate de cobertura bloqueante (docs/quality/testing.md §4): exige >= umbral% de líneas
// cubiertas en src/<Contexto>/Domain y src/<Contexto>/Application (la lógica crítica).
// Uso: php bin/coverage-check.php <umbral> <ruta-clover.xml>

$threshold = (float) ($argv[1] ?? '80');
$cloverPath = $argv[2] ?? 'var/coverage/clover.xml';

if (!is_file($cloverPath)) {
    fwrite(\STDERR, "No se encontró el informe de cobertura: {$cloverPath}\n");
    exit(1);
}

$xml = simplexml_load_file($cloverPath);
if (false === $xml) {
    fwrite(\STDERR, "No se pudo leer el clover XML.\n");
    exit(1);
}

$covered = 0;
$total = 0;
foreach ($xml->xpath('//file') ?: [] as $file) {
    $name = (string) $file['name'];
    if (1 !== preg_match('#/src/[^/]+/(Domain|Application)/#', $name)) {
        continue;
    }
    $metrics = $file->metrics;
    if (null === $metrics) {
        continue;
    }
    $total += (int) $metrics['statements'];
    $covered += (int) $metrics['coveredstatements'];
}

$percentage = $total > 0 ? ($covered / $total) * 100 : 100.0;
printf("Cobertura Domain/Application: %.2f%% (%d/%d sentencias)\n", $percentage, $covered, $total);

if ($percentage < $threshold) {
    fwrite(\STDERR, \sprintf("FALLO: cobertura %.2f%% < umbral %.2f%%\n", $percentage, $threshold));
    exit(1);
}

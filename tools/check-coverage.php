<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php tools/check-coverage.php <clover.xml> <cobertura.xml>\n");
    exit(2);
}

[$script, $cloverFile, $coberturaFile] = $argv;
unset($script);

$clover = new DOMDocument();
$cobertura = new DOMDocument();
if (!$clover->load($cloverFile) || !$cobertura->load($coberturaFile)) {
    fwrite(STDERR, "Cannot read coverage reports.\n");
    exit(2);
}

$cloverXPath = new DOMXPath($clover);
$metricNodes = $cloverXPath->query('/coverage/project/metrics');
$metrics = $metricNodes === false ? null : $metricNodes->item(0);
$coverage = $cobertura->documentElement;
if (!$metrics instanceof DOMElement || !$coverage instanceof DOMElement) {
    fwrite(STDERR, "Coverage reports have an unexpected format.\n");
    exit(2);
}

/** @return float */
$ratio = static function (DOMElement $element, string $covered, string $total): float {
    $totalValue = (int) $element->getAttribute($total);

    return $totalValue === 0 ? 1.0 : (int) $element->getAttribute($covered) / $totalValue;
};

$results = [
    'lines' => $ratio($metrics, 'coveredstatements', 'statements'),
    'methods' => $ratio($metrics, 'coveredmethods', 'methods'),
    'branches' => (float) $coverage->getAttribute('branch-rate'),
];
$failed = false;

foreach ($results as $name => $value) {
    printf("%-10s %6.2f%%\n", ucfirst($name), $value * 100);
    if ($value < 1.0) {
        $failed = true;
    }
}

exit($failed ? 1 : 0);

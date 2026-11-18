<?php

declare(strict_types=1);

exit(main(serverArguments()));

/**
 * @param list<string> $arguments
 */
function main(array $arguments): int
{
    if (count($arguments) !== 3) {
        fwrite(STDERR, "Usage: php tools/check-coverage.php <clover.xml> <minimum-line-coverage>\n");

        return 2;
    }

    $cloverPath = $arguments[1];
    $minimum = (float) $arguments[2];
    $localFile = resolveLocalFile($cloverPath);

    if ($localFile === false) {
        fwrite(STDERR, sprintf("Coverage file not found: %s\n", $cloverPath));

        return 2;
    }

    $document = new DOMDocument();

    if ($document->load($localFile, LIBXML_NONET) === false) {
        fwrite(STDERR, sprintf("Unable to parse coverage file: %s\n", $cloverPath));

        return 2;
    }

    $xpath = new DOMXPath($document);
    $metricNodes = $xpath->query('/coverage/project/metrics');

    if ($metricNodes === false || $metricNodes->length === 0) {
        fwrite(STDERR, "Coverage metrics not found.\n");

        return 2;
    }

    $metricNode = $metricNodes->item(0);

    if (!$metricNode instanceof DOMElement) {
        fwrite(STDERR, "Coverage metrics node is invalid.\n");

        return 2;
    }

    $passed = reportLineCoverage(
        'Overall',
        (int) $metricNode->getAttribute('coveredstatements'),
        (int) $metricNode->getAttribute('statements'),
        $minimum,
    );

    $packages = $document->getElementsByTagName('package');
    for ($index = 0; $index < $packages->length; $index++) {
        $package = $packages->item($index);
        if (!$package instanceof DOMElement) {
            continue;
        }

        $files = $xpath->query('file/metrics', $package);
        if ($files === false || $files->length === 0) {
            fwrite(STDERR, "Namespace coverage metrics not found.\n");

            return 2;
        }

        $covered = 0;
        $total = 0;
        for ($fileIndex = 0; $fileIndex < $files->length; $fileIndex++) {
            $metrics = $files->item($fileIndex);
            if ($metrics instanceof DOMElement) {
                $covered += (int) $metrics->getAttribute('coveredstatements');
                $total += (int) $metrics->getAttribute('statements');
            }
        }

        if ($total > 0 && !reportLineCoverage($package->getAttribute('name'), $covered, $total, $minimum)) {
            $passed = false;
        }
    }

    return $passed ? 0 : 1;
}

function reportLineCoverage(string $scope, int $covered, int $total, float $minimum): bool
{
    $coverage = $total === 0 ? 0.0 : ((float) $covered / (float) $total) * 100.0;
    printf("%s line coverage: %.2f%% (minimum %.2f%%)\n", $scope, $coverage, $minimum);

    return $coverage >= $minimum;
}

/**
 * Accept an existing local file selected by the CLI operator.
 *
 * @psalm-taint-escape file
 * @psalm-taint-escape ssrf
 * @psalm-taint-escape url_component
 * @psalm-taint-escape url_path
 */
function resolveLocalFile(string $path): string|false
{
    $localFile = realpath($path);

    return $localFile !== false && is_file($localFile) ? $localFile : false;
}

/**
 * @return list<string>
 */
function serverArguments(): array
{
    return normalizeArguments($_SERVER['argv'] ?? []);
}

/**
 * @return list<string>
 */
function normalizeArguments(mixed $arguments): array
{
    if (!is_array($arguments)) {
        return [];
    }

    $serverArguments = [];

    foreach ($arguments as $argument) {
        if (!is_string($argument)) {
            return [];
        }

        $serverArguments[] = $argument;
    }

    return $serverArguments;
}

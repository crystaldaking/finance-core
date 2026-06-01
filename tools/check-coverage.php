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

    if (!is_file($cloverPath)) {
        fwrite(STDERR, sprintf("Coverage file not found: %s\n", $cloverPath));

        return 2;
    }

    $document = new DOMDocument();

    if ($document->load($cloverPath) === false) {
        fwrite(STDERR, sprintf("Unable to parse coverage file: %s\n", $cloverPath));

        return 2;
    }

    $metricNodes = (new DOMXPath($document))->query('/coverage/project/metrics');

    if ($metricNodes === false || $metricNodes->length === 0) {
        fwrite(STDERR, "Coverage metrics not found.\n");

        return 2;
    }

    $metricNode = $metricNodes->item(0);

    if (!$metricNode instanceof DOMElement) {
        fwrite(STDERR, "Coverage metrics node is invalid.\n");

        return 2;
    }

    $coveredStatements = (int) $metricNode->getAttribute('coveredstatements');
    $statements = (int) $metricNode->getAttribute('statements');
    $coverage = $statements === 0 ? 0.0 : ((float) $coveredStatements / (float) $statements) * 100.0;

    printf("Line coverage: %.2f%% (minimum %.2f%%)\n", $coverage, $minimum);

    return $coverage >= $minimum ? 0 : 1;
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

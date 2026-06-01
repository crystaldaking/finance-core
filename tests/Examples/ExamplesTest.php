<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Examples;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExamplesTest extends TestCase
{
    #[DataProvider('exampleFiles')]
    public function testExampleRunsSuccessfully(string $exampleFile): void
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($exampleFile);
        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, self::outputToString($output));
        self::assertNotSame([], $output);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function exampleFiles(): array
    {
        $exampleFiles = glob(dirname(__DIR__, 2) . '/examples/*.php');
        $demoFiles = glob(dirname(__DIR__, 2) . '/demo/*.php');
        self::assertNotFalse($exampleFiles);
        self::assertNotFalse($demoFiles);
        $files = [...$exampleFiles, ...$demoFiles];
        sort($files);

        return array_map(static fn (string $file): array => [$file], $files);
    }

    /**
     * @param array<array-key, mixed> $output
     * @psalm-suppress MixedAssignment Output is populated by PHP's exec() API as strings.
     */
    private static function outputToString(array $output): string
    {
        $lines = [];

        foreach ($output as $line) {
            if (is_scalar($line) || $line === null) {
                $lines[] = (string) $line;
            }
        }

        return implode(PHP_EOL, $lines);
    }
}

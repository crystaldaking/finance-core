<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ArchitectureTest extends TestCase
{
    public function testSourceDoesNotUseFrameworkDependencies(): void
    {
        $forbidden = [
            'Illuminate\\',
            'Laravel\\',
            'Symfony\\Component\\HttpFoundation',
            'Symfony\\Bundle\\',
            'Doctrine\\ORM',
            'Carbon\\',
        ];

        foreach ($this->sourceFiles() as $file) {
            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);

            foreach ($forbidden as $needle) {
                self::assertStringNotContainsString($needle, $contents, $file->getPathname());
            }
        }
    }

    public function testFinancialModulesDoNotUseFloatingPointTypes(): void
    {
        foreach ($this->sourceFiles() as $file) {
            if (!str_contains($file->getPathname(), '/src/Money/')
                && !str_contains($file->getPathname(), '/src/Fee/')
                && !str_contains($file->getPathname(), '/src/Ledger/')
            ) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            self::assertDoesNotMatchRegularExpression('/\bfloat\b|\(float\)|is_float|floatval/i', $contents, $file->getPathname());
        }
    }

    public function testEverySourceFileDeclaresStrictTypes(): void
    {
        foreach ($this->sourceFiles() as $file) {
            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            self::assertStringContainsString('declare(strict_types=1);', $contents, $file->getPathname());
        }
    }

    public function testEverySourceFileUsesCoreNamespace(): void
    {
        foreach ($this->sourceFiles() as $file) {
            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            self::assertMatchesRegularExpression('/namespace Crystal\\\\Finance\\\\Core(?:\\\\[^;]+)?;/', $contents, $file->getPathname());
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    private function sourceFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src'),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file;
        }

        return $files;
    }
}

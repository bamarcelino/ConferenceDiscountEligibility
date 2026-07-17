<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SourceContractTest extends TestCase
{
    public function testPluginDoesNotImplementPayPalOrMarkPaymentsPaid(): void
    {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root . '/src/Managers/DiscountAwarePaymentManager.php');
        self::assertIsString($source);
        self::assertStringNotContainsString('fulfillQueued', $source);
        self::assertStringNotContainsString('paid_at', $source);
    }

    public function testPanelPackageManifestAndEntryPoint(): void
    {
        $root = dirname(__DIR__, 2);
        self::assertFileExists($root . '/index.php');
        self::assertFileExists($root . '/index.yaml');
        self::assertStringContainsString('folder: ConferenceDiscountEligibility', (string) file_get_contents($root . '/index.yaml'));
    }
}

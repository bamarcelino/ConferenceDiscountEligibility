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
        self::assertStringContainsString(
            'folder: ConferenceDiscountEligibility',
            (string) file_get_contents($root . '/index.yaml'),
        );
    }

    public function testAuthorFallbackUsesSubmissionEvidenceInTheCurrentConference(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root . '/src/Services/AuthorIdentityVerifier.php');

        self::assertStringContainsString("where('scheduled_conference_id'", $source);
        self::assertStringContainsString("where('user_id'", $source);
        self::assertStringContainsString("whereHas('participants'", $source);
        self::assertStringContainsString("whereHas('role'", $source);
        self::assertStringContainsString('UserRole::Author->value', $source);
        self::assertStringContainsString("whereHas('authors'", $source);
        self::assertStringContainsString('LOWER(TRIM(email))', $source);
        self::assertStringContainsString('submission_author_email', $source);
    }

    public function testSelfAssignableAuthorRoleAloneIsNotAccepted(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root . '/src/Services/AuthorIdentityVerifier.php');

        self::assertStringNotContainsString('hasRole(', $source);
        self::assertStringNotContainsString('roles()', $source);
        self::assertStringNotContainsString('Author::query', $source);
    }

    public function testDomainFallbackIsExplicitAndDefaultsToVerifiedEmail(): void
    {
        $root = dirname(__DIR__, 2);
        $schema = (string) file_get_contents($root . '/src/Database/SchemaDefinition.php');
        $resource = (string) file_get_contents(
            $root . '/src/Panel/ScheduledConference/Resources/InstitutionalDomainResource.php',
        );

        self::assertStringContainsString("default('verified_email_only')", $schema);
        self::assertStringContainsString('VerifiedEmailOrConfirmedAuthor', $resource);
        self::assertStringContainsString('VerifiedEmailOnly', $resource);
    }
}

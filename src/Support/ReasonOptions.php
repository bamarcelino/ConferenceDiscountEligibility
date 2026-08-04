<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

final class ReasonOptions
{
    public const ACTIVE_MEMBER = 'active_member';
    public const INSTITUTIONAL_PARTNER = 'institutional_partner';
    public const COUNTRY_BASED_WAIVER = 'country_based_waiver';
    public const RESEARCH_SUPPORT_PROGRAM = 'research_support_program';
    public const FINANCIAL_HARDSHIP = 'financial_hardship';
    public const PROMOTIONAL_CAMPAIGN = 'promotional_campaign';
    public const EDITORIAL_DECISION = 'editorial_decision';
    public const INDIVIDUAL_APPROVAL = 'individual_approval';
    public const OTHER = 'other';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::ACTIVE_MEMBER => __('ConferenceDiscountEligibility::messages.reason_active_member'),
            self::INSTITUTIONAL_PARTNER => __('ConferenceDiscountEligibility::messages.reason_institutional_partner'),
            self::COUNTRY_BASED_WAIVER => __('ConferenceDiscountEligibility::messages.reason_country_based_waiver'),
            self::RESEARCH_SUPPORT_PROGRAM => __('ConferenceDiscountEligibility::messages.reason_research_support_program'),
            self::FINANCIAL_HARDSHIP => __('ConferenceDiscountEligibility::messages.reason_financial_hardship'),
            self::PROMOTIONAL_CAMPAIGN => __('ConferenceDiscountEligibility::messages.reason_promotional_campaign'),
            self::EDITORIAL_DECISION => __('ConferenceDiscountEligibility::messages.reason_editorial_decision'),
            self::INDIVIDUAL_APPROVAL => __('ConferenceDiscountEligibility::messages.reason_individual_approval'),
            self::OTHER => __('ConferenceDiscountEligibility::messages.reason_other'),
        ];
    }

    /** @return array<string, string> */
    public static function canonicalLabels(): array
    {
        return [
            self::ACTIVE_MEMBER => 'Active member',
            self::INSTITUTIONAL_PARTNER => 'Institutional partner',
            self::COUNTRY_BASED_WAIVER => 'Country-based waiver',
            self::RESEARCH_SUPPORT_PROGRAM => 'Research support program',
            self::FINANCIAL_HARDSHIP => 'Financial hardship',
            self::PROMOTIONAL_CAMPAIGN => 'Promotional campaign',
            self::EDITORIAL_DECISION => 'Editorial decision',
            self::INDIVIDUAL_APPROVAL => 'Individual approval',
            self::OTHER => 'Other',
        ];
    }

    /** @return array{code: string, custom: ?string, details: ?string} */
    public static function parse(?string $reason): array
    {
        $reason = trim((string) $reason);
        $canonical = self::canonicalLabels();

        if (str_starts_with($reason, 'Other: ')) {
            $customAndDetails = substr($reason, strlen('Other: '));
            [$custom, $details] = array_pad(explode(' — ', $customAndDetails, 2), 2, null);

            return ['code' => self::OTHER, 'custom' => $custom, 'details' => $details];
        }

        foreach ($canonical as $code => $label) {
            if ($reason === $label) {
                return ['code' => $code, 'custom' => null, 'details' => null];
            }

            $prefix = $label . ' — ';
            if ($code !== self::OTHER && str_starts_with($reason, $prefix)) {
                return ['code' => $code, 'custom' => null, 'details' => substr($reason, strlen($prefix))];
            }
        }

        $legacy = [
            'CLAEC active member' => self::ACTIVE_MEMBER,
            'Institutional partner affiliate' => self::INSTITUTIONAL_PARTNER,
            'Research4Life Group A' => self::RESEARCH_SUPPORT_PROGRAM,
            'Research4Life Group B' => self::RESEARCH_SUPPORT_PROGRAM,
        ];

        if (isset($legacy[$reason])) {
            return ['code' => $legacy[$reason], 'custom' => null, 'details' => $reason];
        }

        if ($reason === '' || $reason === 'Other') {
            return ['code' => self::OTHER, 'custom' => null, 'details' => null];
        }

        return ['code' => self::OTHER, 'custom' => $reason, 'details' => null];
    }

    public static function compose(?string $code, ?string $custom, ?string $details): string
    {
        $code = array_key_exists((string) $code, self::canonicalLabels()) ? (string) $code : self::OTHER;
        $custom = trim((string) $custom);
        $details = trim((string) $details);
        $base = $code === self::OTHER ? ($custom === '' ? '' : 'Other: ' . $custom) : self::canonicalLabels()[$code];

        if ($base === '') {
            return '';
        }

        return $details === '' ? $base : $base . ' — ' . $details;
    }
}

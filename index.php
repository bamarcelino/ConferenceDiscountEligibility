<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException('Conference Discount Eligibility is missing vendor/autoload.php. Install the official release ZIP instead of the source-code archive.');
}

require_once $autoload;

return new ConferenceDiscountEligibility\ConferenceDiscountEligibilityPlugin();

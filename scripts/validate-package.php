<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/validate-package.php <archive.zip|archive.tar.gz>\n");
    exit(2);
}
$archive = realpath($argv[1]);
if ($archive === false || ! is_file($archive)) {
    fwrite(STDERR, "Archive not found.\n");
    exit(2);
}
$temp = sys_get_temp_dir() . '/cde-package-' . bin2hex(random_bytes(6));
mkdir($temp, 0700, true);
$command = str_ends_with($archive, '.zip')
    ? 'unzip -q ' . escapeshellarg($archive) . ' -d ' . escapeshellarg($temp)
    : 'tar -xzf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($temp);
exec($command . ' 2>&1', $output, $code);
if ($code !== 0) {
    fwrite(STDERR, "Extraction failed:\n" . implode("\n", $output) . "\n");
    exit(1);
}
$root = $temp . '/ConferenceDiscountEligibility';
$required = ['index.php','index.yaml','composer.json','vendor/autoload.php','src/ConferenceDiscountEligibilityPlugin.php','RESEARCH.md','ARCHITECTURE.md','SECURITY.md','sample-discount-import.csv'];
$errors = [];
$entries = array_values(array_diff(scandir($temp) ?: [], ['.','..']));
if ($entries !== ['ConferenceDiscountEligibility']) {
    $errors[] = 'Archive must contain exactly one ConferenceDiscountEligibility root folder.';
}
foreach ($required as $file) {
    if (! is_file($root . '/' . $file)) { $errors[] = 'Missing ' . $file; }
}
if (is_dir($root . '/ConferenceDiscountEligibility')) {
    $errors[] = 'Duplicate nested root folder detected.';
}
if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}
echo "Package structure valid: one root folder; index.php and index.yaml are at the expected level.\n";

<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$files = [];
foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['php', 'blade.php'], true)) {
        $files[] = $file->getPathname();
    } elseif ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $files[] = $file->getPathname();
    }
}
sort($files);
$failed = 0;
foreach ($files as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    exec($command, $output, $code);
    if ($code !== 0) {
        $failed++;
        fwrite(STDERR, implode("\n", $output) . "\n");
    }
    $output = [];
}
printf("Linted %d PHP/Blade files; %d failed.\n", count($files), $failed);
exit($failed === 0 ? 0 : 1);

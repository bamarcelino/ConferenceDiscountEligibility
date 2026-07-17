<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = ['src', 'index.php', 'index.yaml', 'resources', 'lang', 'database', 'vendor/autoload.php'];
$patterns = [
    '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    '/\bAKIA[0-9A-Z]{16}\b/',
    '/\bAIza[0-9A-Za-z_-]{30,}\b/',
    '/\b(?:client_secret|paypal_secret|password|api_token)\s*[=:>]\s*[\'\"][^\'\"]{8,}[\'\"]/i',
];
$findings = [];
foreach ($paths as $path) {
    $target = $root . '/' . $path;
    $files = is_dir($target)
        ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS))
        : [new SplFileInfo($target)];
    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) { continue; }
        $content = file_get_contents($file->getPathname());
        if (! is_string($content)) { continue; }
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content) === 1) {
                $findings[] = str_replace($root . '/', '', $file->getPathname()) . ' matches ' . $pattern;
            }
        }
    }
}
if ($findings !== []) {
    fwrite(STDERR, implode("\n", $findings) . "\n");
    exit(1);
}
echo "Secret scan passed; no credential-like material was detected in runtime files.\n";

<?php

declare(strict_types=1);

namespace Filament { class Panel {} }
namespace App\Classes {
    use Filament\Panel;

    class Plugin
    {
        protected array $info = [];
        protected string $pluginPath;
        /** @var array<string, mixed> */
        public static array $settings = [];

        public function setPluginPath(string $path): static { $this->pluginPath = $path; return $this; }
        public function load(): static
        {
            $manifest = (string) file_get_contents($this->pluginPath . '/index.yaml');
            preg_match('/^folder:\s*([^\r\n]+)/m', $manifest, $folder);
            preg_match('/^sitewide:\s*(true|false)/m', $manifest, $sitewide);
            $this->info = ['folder' => trim($folder[1] ?? ''), 'sitewide' => ($sitewide[1] ?? '') === 'true'];
            return $this;
        }
        public function getSetting(string $key, mixed $default = null): mixed { return self::$settings[$key] ?? $default; }
        public function updateSetting(string $key, mixed $value): mixed { self::$settings[$key] = $value; return true; }
        public function boot() {}
        public function onPanel(Panel $panel): void {}
    }
}
namespace {
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $plugin = require dirname(__DIR__) . '/index.php';
    $plugin->setPluginPath(dirname(__DIR__))->load();

    if (App\Classes\Plugin::$settings['enabled'] !== true || ! $plugin->isEnabled()) {
        fwrite(STDERR, "First discovery did not persist and expose sitewide enablement.\n");
        exit(1);
    }

    App\Classes\Plugin::$settings['enabled'] = false;
    $plugin->load();
    if ($plugin->isEnabled()) {
        fwrite(STDERR, "Explicitly disabled state was overwritten during rediscovery.\n");
        exit(1);
    }

    echo "Leconfe 1.4.6/1.5.0 discovery enablement simulation passed.\n";
}

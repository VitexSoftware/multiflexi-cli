<?php

require_once '/usr/share/php/Composer/InstalledVersions.php';
if (\PHP_VERSION_ID < 80000 && file_exists('/usr/share/php/Symfony/Polyfill/php80/bootstrap.php')) {
    require_once '/usr/share/php/Symfony/Polyfill/php80/Php80.php';
    require_once '/usr/share/php/Symfony/Polyfill/php80/bootstrap.php';
}
require_once '/usr/share/php/MultiFlexi/autoload.php';
require_once '/usr/share/php/Symfony/Component/Process/autoload.php';
require_once '/usr/share/php/Symfony/Component/Console/autoload.php';
//require_once '/usr/share/php/JsonSchema/autoload.php';
require_once '/usr/share/php/LucidFrame/autoload.php';

spl_autoload_register(function (string $class): void {
    $map = [
        'MultiFlexi\\Cli\\Command\\' => '/usr/lib/multiflexi-cli/Command/',
        'MultiFlexi\\Cli\\'          => '/usr/lib/multiflexi-cli/MultiFlexi/Cli/',
        'MultiFlexi\\'               => '/usr/lib/multiflexi-cli/MultiFlexi/',
    ];
    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) {
                require $file;
            }
            return;
        }
    }
});

(function (): void {
    $versions = [];
    foreach (\Composer\InstalledVersions::getAllRawData() as $d) {
        $versions = array_merge($versions, $d['versions'] ?? []);
    }
    $name    = 'unknown';
    $version = '0.0.0';
    $versions[$name] = ['pretty_version' => $version, 'version' => $version,
        'reference' => null, 'type' => 'project', 'install_path' => __DIR__,
        'aliases' => [], 'dev_requirement' => false];
    \Composer\InstalledVersions::reload([
        'root' => ['name' => $name, 'pretty_version' => $version, 'version' => $version,
            'reference' => null, 'type' => 'project', 'install_path' => __DIR__,
            'aliases' => [], 'dev' => false],
        'versions' => $versions,
    ]);
})();

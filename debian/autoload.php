<?php
// Debian autoloader for multiflexi-cli
// Load dependency autoloaders
require_once '/usr/share/php/MultiFlexi/autoload.php';
require_once '/usr/share/php/EaseCore/autoload.php';
require_once '/usr/share/php/EaseFluentPDO/autoload.php';
require_once '/usr/share/php/EaseHtml/autoload.php';
require_once '/usr/share/php/Symfony/Component/Process/autoload.php';
require_once '/usr/share/php/Symfony/Component/Console/autoload.php';
require_once '/usr/share/php/JsonSchema/autoload.php';

// PSR-4 autoloader for application classes
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'MultiFlexi\\Cli\\Command\\' => '/usr/lib/multiflexi-cli/Command/',
        'MultiFlexi\\Cli\\' => '/usr/lib/multiflexi-cli/MultiFlexi/Cli/',
        'MultiFlexi\\' => '/usr/lib/multiflexi-cli/MultiFlexi/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

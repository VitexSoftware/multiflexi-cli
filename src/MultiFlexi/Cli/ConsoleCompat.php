<?php

declare(strict_types=1);

/**
 * This file is part of the MultiFlexi package
 *
 * https://multiflexi.eu/
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MultiFlexi\Cli;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Compatibility shim for registering commands with Symfony Console.
 *
 * multiflexi-cli ships as a Debian package that autoloads the *system*
 * php-symfony-console package via pkg-php-tools/dh_phpcomposer (see
 * debian/rules) instead of a bundled vendor/ copy, so the Symfony Console
 * version at runtime is whatever the target distro currently packages —
 * not necessarily what composer.json declares. Supported distros (see
 * multiflexi-doc-en platform-support.rst) currently ship Symfony Console
 * 6.x, where commands are registered via Application::add(). Symfony
 * Console 8.x renamed that method to Application::addCommand() and
 * removed add() entirely, which is what composer.json's "symfony/console"
 * constraint resolves to for a plain `composer install` (e.g. local dev,
 * or any future distro that packages Symfony Console >= 7).
 *
 * TODO: once every supported distro (see platform-support.rst) ships only
 * Symfony Console >= 7 (addCommand()-only), drop this shim and call
 * Application::addCommand() directly at every call site.
 */
final class ConsoleCompat
{
    public static function addCommand(Application $application, Command $command): void
    {
        if (method_exists($application, 'addCommand')) {
            $application->addCommand($command);
        } else {
            $application->add($command);
        }
    }
}

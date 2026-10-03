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

namespace MultiFlexi\Cli\Command\Service;

class StartCommand extends ControlCommand
{
    protected static $defaultName = 'service:start';

    protected function configure(): void
    {
        $this->configureControl('service:start', 'Start a MultiFlexi systemd service/timer');
    }

    protected function action(): string
    {
        return 'start';
    }
}

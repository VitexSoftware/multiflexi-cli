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

class StopCommand extends ControlCommand
{
    protected static $defaultName = 'service:stop';

    protected function configure(): void
    {
        $this->configureControl('service:stop', 'Stop a MultiFlexi systemd service/timer');
    }

    protected function action(): string
    {
        return 'stop';
    }
}

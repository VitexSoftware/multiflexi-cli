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

namespace Test\MultiFlexi\Cli\Command\Service;

use MultiFlexi\Cli\Command\Service\Units;
use PHPUnit\Framework\TestCase;

class UnitsTest extends TestCase
{
    public function testKnownUnits(): void
    {
        $this->assertSame('multiflexi-scheduler.service', Units::resolve('scheduler'));
        $this->assertSame('multiflexi-executor.service', Units::resolve('executor'));
        $this->assertSame('multiflexi-housekeeper.timer', Units::resolve('housekeeper'));
        $this->assertSame('multiflexi-scheduler.service', Units::resolve('Scheduler'));
    }

    public function testUnknownUnit(): void
    {
        $this->assertNull(Units::resolve('nosuch'));
    }
}

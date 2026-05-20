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

namespace Test\MultiFlexi\Cli\Command;

use MultiFlexi\Cli\Command\RunTemplate\ScheduleCommand;

class RunTemplateTest extends \PHPUnit\Framework\TestCase
{
    protected ScheduleCommand $schedule;

    protected function setUp(): void
    {
        $this->schedule = new ScheduleCommand();
    }

    public function testScheduleCommandHasEnvOption(): void
    {
        $definition = $this->schedule->getDefinition();
        $this->assertTrue($definition->hasOption('env'), '--env option must be defined on run-template:schedule');
    }

    public function testScheduleCommandHasConfigOption(): void
    {
        $definition = $this->schedule->getDefinition();
        $this->assertTrue($definition->hasOption('config'), '--config option must be defined on run-template:schedule');
    }

    public function testScheduleCommandHasScheduleTimeOption(): void
    {
        $definition = $this->schedule->getDefinition();
        $this->assertTrue($definition->hasOption('schedule_time'), '--schedule_time option must be defined on run-template:schedule');
    }

    public function testEnvOptionIsArray(): void
    {
        $definition = $this->schedule->getDefinition();
        $option = $definition->getOption('env');
        $this->assertTrue($option->isArray(), '--env must accept multiple values (VALUE_IS_ARRAY)');
    }

    public function testConfigOptionIsArray(): void
    {
        $definition = $this->schedule->getDefinition();
        $option = $definition->getOption('config');
        $this->assertTrue($option->isArray(), '--config must accept multiple values (VALUE_IS_ARRAY)');
    }
}

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

use MultiFlexi\Cli\Command\RunTemplate\AssignCredentialCommand;
use MultiFlexi\Cli\Command\RunTemplate\GetCommand;
use MultiFlexi\Cli\Command\RunTemplate\ListCredentialsCommand;
use MultiFlexi\Cli\Command\RunTemplate\ScheduleCommand;
use MultiFlexi\Cli\Command\RunTemplate\UnassignCredentialCommand;

class RunTemplateTest extends \PHPUnit\Framework\TestCase
{
    protected ScheduleCommand $schedule;
    protected GetCommand $get;
    protected AssignCredentialCommand $assignCredential;
    protected UnassignCredentialCommand $unassignCredential;
    protected ListCredentialsCommand $listCredentials;

    protected function setUp(): void
    {
        $this->schedule = new ScheduleCommand();
        $this->get = new GetCommand();
        $this->assignCredential = new AssignCredentialCommand();
        $this->unassignCredential = new UnassignCredentialCommand();
        $this->listCredentials = new ListCredentialsCommand();
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

    public function testGetCommandHasFieldsOption(): void
    {
        $definition = $this->get->getDefinition();
        $this->assertTrue($definition->hasOption('fields'), '--fields option must be defined on run-template:get');
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

    public function testAssignCredentialCommandName(): void
    {
        $this->assertSame('run-template:assign-credential', $this->assignCredential->getName());
    }

    public function testAssignCredentialCommandHasIdOption(): void
    {
        $definition = $this->assignCredential->getDefinition();
        $this->assertTrue($definition->hasOption('id'), '--id option must be defined on run-template:assign-credential');
    }

    public function testAssignCredentialCommandHasCredentialIdOption(): void
    {
        $definition = $this->assignCredential->getDefinition();
        $this->assertTrue($definition->hasOption('credential_id'), '--credential_id option must be defined on run-template:assign-credential');
    }

    public function testAssignCredentialCommandHasFormatOption(): void
    {
        $definition = $this->assignCredential->getDefinition();
        $this->assertTrue($definition->hasOption('format'), '--format option must be defined on run-template:assign-credential');
    }

    public function testUnassignCredentialCommandName(): void
    {
        $this->assertSame('run-template:unassign-credential', $this->unassignCredential->getName());
    }

    public function testUnassignCredentialCommandHasIdOption(): void
    {
        $definition = $this->unassignCredential->getDefinition();
        $this->assertTrue($definition->hasOption('id'), '--id option must be defined on run-template:unassign-credential');
    }

    public function testUnassignCredentialCommandHasCredentialIdOption(): void
    {
        $definition = $this->unassignCredential->getDefinition();
        $this->assertTrue($definition->hasOption('credential_id'), '--credential_id option must be defined on run-template:unassign-credential');
    }

    public function testUnassignCredentialCommandHasFormatOption(): void
    {
        $definition = $this->unassignCredential->getDefinition();
        $this->assertTrue($definition->hasOption('format'), '--format option must be defined on run-template:unassign-credential');
    }

    public function testListCredentialsCommandName(): void
    {
        $this->assertSame('run-template:list-credentials', $this->listCredentials->getName());
    }

    public function testListCredentialsCommandHasIdOption(): void
    {
        $definition = $this->listCredentials->getDefinition();
        $this->assertTrue($definition->hasOption('id'), '--id option must be defined on run-template:list-credentials');
    }

    public function testListCredentialsCommandHasFormatOption(): void
    {
        $definition = $this->listCredentials->getDefinition();
        $this->assertTrue($definition->hasOption('format'), '--format option must be defined on run-template:list-credentials');
    }
}

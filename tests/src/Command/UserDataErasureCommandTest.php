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

use MultiFlexi\Cli\Command\UserDataErasureCommand;

/**
 * Tests for UserDataErasureCommand.
 */
class UserDataErasureCommandTest extends \PHPUnit\Framework\TestCase
{
    protected UserDataErasureCommand $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new UserDataErasureCommand();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * Test that the command name is correctly set for Symfony Console 7.x compatibility.
     */
    public function testCommandName(): void
    {
        $this->assertSame('user:data-erasure', $this->object->getName());
    }

    /**
     * Test that the command can be found by name in a Symfony Application.
     */
    public function testCommandIsRegistrable(): void
    {
        $application = new \Symfony\Component\Console\Application();
        $application->add($this->object);
        $this->assertSame('user:data-erasure', $application->find('user:data-erasure')->getName());
    }
}

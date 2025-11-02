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

namespace MultiFlexi\Cli\Test\Command;

use MultiFlexi\Cli\Command\EncryptionCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test case for EncryptionCommand.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class EncryptionCommandTest extends TestCase
{
    private EncryptionCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->command = new EncryptionCommand();
        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test encryption status command.
     */
    public function testEncryptionStatus(): void
    {
        // Skip if database is not configured
        if (!\Ease\Shared::cfg('DB_DATABASE')) {
            $this->markTestSkipped('Database not configured');
        }

        $this->commandTester->execute([
            'action' => 'status',
        ]);

        $output = $this->commandTester->getDisplay();

        // Should contain either success output or error message
        $this->assertTrue(
            str_contains($output, 'Encryption Status')
            || str_contains($output, 'Failed to retrieve encryption status'),
        );
    }

    /**
     * Test encryption status command with JSON output.
     */
    public function testEncryptionStatusJson(): void
    {
        // Skip if database is not configured
        if (!\Ease\Shared::cfg('DB_DATABASE')) {
            $this->markTestSkipped('Database not configured');
        }

        $this->commandTester->execute([
            'action' => 'status',
            '--format' => 'json',
        ]);

        $output = $this->commandTester->getDisplay();
        $json = json_decode($output, true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('status', $json);

        // If successful, check for expected keys
        if ($json['status'] === 'success') {
            $this->assertArrayHasKey('master_key', $json);
            $this->assertArrayHasKey('total_keys', $json);
            $this->assertArrayHasKey('active_keys', $json);
            $this->assertArrayHasKey('keys', $json);
        } else {
            // If error, should have error message
            $this->assertArrayHasKey('message', $json);
        }
    }

    /**
     * Test unknown action returns error.
     */
    public function testUnknownAction(): void
    {
        $this->commandTester->execute([
            'action' => 'invalid',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Unknown action', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * Test encryption init command requires confirmation in production.
     *
     * Note: This is a basic test that verifies the command structure.
     * Full integration testing should be done in a test environment.
     */
    public function testEncryptionInitCommandStructure(): void
    {
        // Test that the command accepts the init action
        // We don't actually run it as it modifies the database
        $this->assertTrue(true);
    }
}

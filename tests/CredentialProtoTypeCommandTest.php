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

use MultiFlexi\Cli\Command\CredentialProtoTypeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for CredentialProtoTypeCommand.
 *
 * @covers \MultiFlexi\Cli\Command\CredentialProtoTypeCommand
 */
class CredentialProtoTypeCommandTest extends TestCase
{
    /**
     * Test crprototype list action returns JSON when requested.
     */
    public function testListJsonOutput(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'list',
            '--format' => 'json',
        ]);

        $output = $tester->getDisplay();
        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
    }

    /**
     * Test crprototype list action returns text by default.
     */
    public function testListDefaultTextOutput(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'list',
        ]);

        $output = $tester->getDisplay();
        // Should not be valid JSON (default is text format)
        $this->expectException(JsonException::class);
        json_decode($output, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * Test crprototype create with required fields.
     */
    public function testCreateWithValidData(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'create',
            '--code' => 'TestCred123',
            '--name' => 'Test Credential',
            '--uuid' => '12345678-1234-5678-9abc-123456789abc',
            '--format' => 'json',
        ]);

        $output = $tester->getDisplay();
        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('TestCred123', $data['code']);
    }

    /**
     * Test crprototype create missing required fields.
     */
    public function testCreateMissingRequiredFields(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'create',
            '--name' => 'Test Credential',
            '--format' => 'json',
        ]);

        $this->assertEquals(1, $tester->getStatusCode()); // Should fail

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Missing required field', $output);
    }

    /**
     * Test import-json with missing file.
     */
    public function testImportJsonMissingFile(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'import-json',
            '--file' => '/nonexistent/file.json',
            '--format' => 'json',
        ]);

        $this->assertEquals(1, $tester->getStatusCode()); // Should fail

        $output = $tester->getDisplay();
        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('Missing or invalid', $data['message']);
    }

    /**
     * Test import-json with directory instead of file.
     */
    public function testImportJsonWithDirectory(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        // Use /tmp as an existing directory
        $tester->execute([
            'action' => 'import-json',
            '--file' => '/tmp',
            '--format' => 'json',
        ]);

        $this->assertEquals(1, $tester->getStatusCode()); // Should fail

        $output = $tester->getDisplay();
        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('must be a file, not a directory', $data['message']);
    }

    /**
     * Test validate-json with missing file.
     */
    public function testValidateJsonMissingFile(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'validate-json',
            '--file' => '/nonexistent/file.json',
            '--format' => 'json',
        ]);

        $this->assertEquals(1, $tester->getStatusCode()); // Should fail

        $output = $tester->getDisplay();
        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('Missing or invalid', $data['message']);
    }

    /**
     * Test get with missing identifiers.
     */
    public function testGetMissingIdentifiers(): void
    {
        $application = new Application();
        $application->add(new CredentialProtoTypeCommand());
        $command = $application->find('crprototype');
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'get',
        ]);

        $this->assertEquals(1, $tester->getStatusCode()); // Should fail

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Missing --id, --uuid, or --code', $output);
    }
}

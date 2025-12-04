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

namespace Tests\MultiFlexi\Cli\Command;

use MultiFlexi\Cli\Command\CredentialTypeCommand;
use MultiFlexi\CredentialProtoType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Integration test for CredentialTypeCommand.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 *
 * @covers \MultiFlexi\Command\CredentialTypeCommand
 */
final class CredentialTypeCommandIntegrationTest extends TestCase
{
    private Application $application;
    private CredentialTypeCommand $command;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->command = new CredentialTypeCommand();
        $this->application->add($this->command);
    }

    /**
     * Test validate-json command with valid JSON.
     */
    public function testValidateJsonValid(): void
    {
        // Create valid test JSON
        $testData = [
            'code' => 'TestCredType',
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'name' => 'Test Credential Type',
            'description' => 'A test credential type',
            'version' => '1.0',
            'fields' => []
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'credtype_test_valid_');
        file_put_contents($tempFile, json_encode($testData, JSON_PRETTY_PRINT));

        try {
            $commandTester = new CommandTester($this->command);
            $commandTester->execute([
                'command' => $this->command->getName(),
                'action' => 'validate-json',
                'json' => $tempFile
            ]);

            $output = $commandTester->getDisplay();
            $this->assertStringContainsString('valid', $output);
            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test validate-json command with invalid JSON.
     */
    public function testValidateJsonInvalid(): void
    {
        // Create invalid test JSON
        $testData = [
            'code' => 'TestCredType',
            // Missing required fields like uuid, name, description, version, fields
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'credtype_test_invalid_');
        file_put_contents($tempFile, json_encode($testData, JSON_PRETTY_PRINT));

        try {
            $commandTester = new CommandTester($this->command);
            $commandTester->execute([
                'command' => $this->command->getName(),
                'action' => 'validate-json',
                'json' => $tempFile
            ]);

            $output = $commandTester->getDisplay();
            $this->assertStringContainsString('validation failed', $output);
            $this->assertEquals(1, $commandTester->getStatusCode());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test validate-json command with JSON format output.
     */
    public function testValidateJsonWithJsonFormat(): void
    {
        // Create valid test JSON
        $testData = [
            'code' => 'TestCredType',
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'name' => 'Test Credential Type',
            'description' => 'A test credential type',
            'version' => '1.0',
            'fields' => []
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'credtype_test_json_format_');
        file_put_contents($tempFile, json_encode($testData, JSON_PRETTY_PRINT));

        try {
            $commandTester = new CommandTester($this->command);
            $commandTester->execute([
                'command' => $this->command->getName(),
                'action' => 'validate-json',
                'json' => $tempFile,
                '--format' => 'json'
            ]);

            $output = $commandTester->getDisplay();
            $outputData = json_decode($output, true);
            
            $this->assertIsArray($outputData, 'Output should be valid JSON');
            $this->assertArrayHasKey('status', $outputData);
            $this->assertArrayHasKey('file', $outputData);
            $this->assertArrayHasKey('schema', $outputData);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test validate-json command with missing file.
     */
    public function testValidateJsonMissingFile(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'command' => $this->command->getName(),
            'action' => 'validate-json',
            'json' => '/nonexistent/file.json'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('not found', $output);
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    /**
     * Test validateCredTypeJson method uses correct schema.
     */
    public function testValidateCredTypeJsonUsesCorrectSchema(): void
    {
        $command = new CredentialTypeCommand();
        
        // Create a reflection to access the private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('validateCredTypeJson');
        $method->setAccessible(true);
        
        // Create temporary valid JSON file
        $testData = [
            'code' => 'TestCredType',
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'name' => 'Test Credential Type',
            'description' => 'A test credential type',
            'version' => '1.0',
            'fields' => []
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'credtype_schema_test_');
        file_put_contents($tempFile, json_encode($testData, JSON_PRETTY_PRINT));

        try {
            $result = $method->invoke($command, $tempFile);
            $this->assertIsArray($result, 'validateCredTypeJson should return array');
        } finally {
            unlink($tempFile);
        }
    }
}
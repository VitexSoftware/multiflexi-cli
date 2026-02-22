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

use MultiFlexi\Cli\Command\QueueCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for QueueCommand - queue operations including fix and overview.
 */
class QueueCommandTest extends \PHPUnit\Framework\TestCase
{
    protected QueueCommand $object;
    protected CommandTester $commandTester;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new QueueCommand();

        $application = new Application();
        $application->add($this->object);

        $command = $application->find('queue');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Tears down the fixture.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * Test that the queue command is properly configured.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::configure
     */
    public function testConfigure(): void
    {
        $definition = $this->object->getDefinition();

        $this->assertTrue($definition->hasArgument('action'));
        $this->assertTrue($definition->hasOption('format'));
        $this->assertTrue($definition->hasOption('limit'));
        $this->assertTrue($definition->hasOption('order'));
        $this->assertTrue($definition->hasOption('direction'));
    }

    /**
     * Test that the action argument accepts 'fix' as a valid value.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::configure
     */
    public function testConfigureIncludesFix(): void
    {
        $definition = $this->object->getDefinition();
        $argument = $definition->getArgument('action');

        $this->assertStringContainsString('fix', $argument->getDescription());
    }

    /**
     * Test queue overview action produces output with Orphaned jobs metric.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testOverviewContainsOrphanedJobsMetric(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Orphaned jobs', $output);
        $this->assertStringContainsString('Total jobs in queue', $output);
    }

    /**
     * Test queue overview action in JSON format includes orphaned_jobs key.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testOverviewJsonContainsOrphanedJobs(): void
    {
        $this->commandTester->execute(['--format' => 'json']);

        $output = $this->commandTester->getDisplay();
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('comprehensive_queue_metrics', $data);
        $this->assertArrayHasKey('orphaned_jobs', $data['comprehensive_queue_metrics']);
        $this->assertIsInt($data['comprehensive_queue_metrics']['orphaned_jobs']);
    }

    /**
     * Test queue overview JSON structure includes schedule_breakdown.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testOverviewJsonStructure(): void
    {
        $this->commandTester->execute(['--format' => 'json']);

        $output = $this->commandTester->getDisplay();
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $metrics = $data['comprehensive_queue_metrics'];

        $this->assertArrayHasKey('total_jobs_in_queue', $metrics);
        $this->assertArrayHasKey('orphaned_jobs', $metrics);
        $this->assertArrayHasKey('unique_applications', $metrics);
        $this->assertArrayHasKey('unique_companies', $metrics);
        $this->assertArrayHasKey('unique_runtemplates', $metrics);
        $this->assertArrayHasKey('schedule_breakdown', $metrics);
    }

    /**
     * Test queue fix action completes successfully.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testFixActionReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute(['action' => 'fix']);

        $this->assertSame(0, $exitCode);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Queue diagnostics and fix completed', $output);
    }

    /**
     * Test queue fix action in JSON format returns proper structure.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testFixActionJsonOutput(): void
    {
        $exitCode = $this->commandTester->execute([
            'action' => 'fix',
            '--format' => 'json',
        ]);

        $this->assertSame(0, $exitCode);

        $output = $this->commandTester->getDisplay();
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertSame('success', $data['status']);
        $this->assertStringContainsString('Queue diagnostics and fix completed', $data['message']);
        $this->assertArrayHasKey('messages', $data);
        $this->assertIsArray($data['messages']);
    }

    /**
     * Test queue fix is idempotent - running twice should not error.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testFixActionIsIdempotent(): void
    {
        // First run
        $exitCode1 = $this->commandTester->execute(['action' => 'fix']);
        $this->assertSame(0, $exitCode1);

        // Second run - should still succeed with nothing to clean
        $exitCode2 = $this->commandTester->execute(['action' => 'fix']);
        $this->assertSame(0, $exitCode2);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Queue diagnostics and fix completed', $output);
    }

    /**
     * Test that after running fix, overview shows zero orphaned jobs.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testFixReducesOrphanedJobsToZero(): void
    {
        // Run fix first
        $this->commandTester->execute(['action' => 'fix']);

        // Now check overview JSON
        $this->commandTester->execute(['--format' => 'json']);

        $output = $this->commandTester->getDisplay();
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertSame(0, $data['comprehensive_queue_metrics']['orphaned_jobs']);
    }

    /**
     * Test unknown action returns failure.
     *
     * @covers \MultiFlexi\Cli\Command\QueueCommand::execute
     */
    public function testUnknownActionReturnsFailure(): void
    {
        $exitCode = $this->commandTester->execute(['action' => 'nonexistent']);

        $this->assertSame(1, $exitCode);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Unknown action', $output);
    }
}

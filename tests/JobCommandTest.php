<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MultiFlexi\Cli\Command\JobCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

/**
 * Unit tests for JobCommand.
 *
 * @covers \MultiFlexi\Cli\Command\JobCommand
 */
class JobCommandTest extends TestCase
{
    /**
     * Test job status action returns JSON when requested.
     */
    public function testStatusJsonOutput(): void
    {
        $application = new Application();
        $application->add(new JobCommand());
        $command = $application->find('job');
        $tester = new CommandTester($command);
        $tester->execute([
            'action' => 'status',
            '--format' => 'json',
        ]);
        $output = $tester->getDisplay();
        $this->assertJson($output);
        $data = json_decode($output, true);
        $this->assertArrayHasKey('total_jobs', $data);
    }

    /**
     * Test job list action returns JSON when requested.
     */
    public function testListJsonOutput(): void
    {
        $application = new Application();
        $application->add(new JobCommand());
        $command = $application->find('job');
        $tester = new CommandTester($command);
        $tester->execute([
            'action' => 'list',
            '--format' => 'json',
        ]);
        $output = $tester->getDisplay();
        $this->assertJson($output);
    }

    /**
     * Test job get action with missing id returns error.
     */
    public function testGetMissingId(): void
    {
        $application = new Application();
        $application->add(new JobCommand());
        $command = $application->find('job');
        $tester = new CommandTester($command);
        $tester->execute([
            'action' => 'get',
            '--format' => 'json',
        ]);
        $output = $tester->getDisplay();
        $this->assertJson($output);
        $data = json_decode($output, true);
        $this->assertStringContainsString('Missing', $output);
    }

    /**
     * Test job create with missing parameters returns error.
     */
    public function testCreateMissingParams(): void
    {
        $application = new Application();
        $application->add(new JobCommand());
        $command = $application->find('job');
        $tester = new CommandTester($command);
        $tester->execute([
            'action' => 'create',
            '--format' => 'json',
        ]);
        $output = $tester->getDisplay();
        $this->assertJson($output);
        $data = json_decode($output, true);
        $this->assertStringContainsString('Failed', $output);
    }
}

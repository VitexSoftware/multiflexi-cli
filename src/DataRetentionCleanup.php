<?php

declare(strict_types=1);

/**
 * This file is part of the MultiFlexi CLI package
 *
 * https://multiflexi.eu/
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MultiFlexi\Cli;

use DateTime;
use Exception;
use MultiFlexi\DataRetention\RetentionService;
use MultiFlexi\DataRetention\DataArchiver;
use MultiFlexi\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Data Retention Cleanup Command
 * 
 * Command-line interface for automated data retention cleanup.
 * Handles scheduled cleanup, grace period processing, and retention reporting.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class DataRetentionCleanup extends Command
{
    /**
     * @var string Command name
     */
    protected static $defaultName = 'retention:cleanup';

    /**
     * @var RetentionService
     */
    private RetentionService $retentionService;

    /**
     * @var DataArchiver
     */
    private DataArchiver $archiver;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->retentionService = new RetentionService();
        $this->archiver = new DataArchiver();
    }

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('retention:cleanup')
            ->setDescription('GDPR Data Retention Cleanup - Automated data retention and deletion')
            ->setHelp('This command performs automated data retention cleanup according to defined GDPR policies.')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Action to perform: calculate, cleanup, grace-period, archive-cleanup, report, or status'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Simulate the cleanup without making actual changes'
            )
            ->addOption(
                'policy',
                'p',
                InputOption::VALUE_REQUIRED,
                'Specific retention policy name to process'
            )
            ->addOption(
                'table',
                't',
                InputOption::VALUE_REQUIRED,
                'Specific table to process'
            )
            ->addOption(
                'days',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of days for retention calculations or reporting',
                '30'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format for reports: text, json, csv',
                'text'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file path for reports'
            )
            ->addOption(
                'verbose',
                'v',
                InputOption::VALUE_NONE,
                'Verbose output with detailed information'
            );
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input Input interface
     * @param OutputInterface $output Output interface
     * @return int Exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $dryRun = $input->getOption('dry-run');
        $verbose = $input->getOption('verbose');

        $io->title('MultiFlexi GDPR Data Retention Cleanup');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No actual changes will be made');
        }

        try {
            switch ($action) {
                case 'calculate':
                    return $this->calculateRetentionDates($io, $input);

                case 'cleanup':
                    return $this->performCleanup($io, $input);

                case 'grace-period':
                    return $this->processGracePeriod($io, $input);

                case 'archive-cleanup':
                    return $this->cleanupArchives($io, $input);

                case 'report':
                    return $this->generateReport($io, $input);

                case 'status':
                    return $this->showStatus($io, $input);

                default:
                    $io->error(sprintf('Unknown action: %s', $action));
                    $io->text('Available actions: calculate, cleanup, grace-period, archive-cleanup, report, status');
                    return Command::FAILURE;
            }
        } catch (Exception $e) {
            $io->error(sprintf('Command failed: %s', $e->getMessage()));
            
            if ($verbose) {
                $io->text($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Calculate retention expiration dates for all records
     */
    private function calculateRetentionDates(SymfonyStyle $io, InputInterface $input): int
    {
        $io->section('Calculating Retention Dates');

        $summary = $this->retentionService->calculateRetentionDates();

        $io->success(sprintf(
            'Updated %d records across %d tables',
            $summary['updated_records'],
            $summary['updated_tables']
        ));

        if (!empty($summary['errors'])) {
            $io->warning('Errors occurred during processing:');
            foreach ($summary['errors'] as $error) {
                $io->text('  - ' . $error);
            }
        }

        return empty($summary['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Perform scheduled cleanup
     */
    private function performCleanup(SymfonyStyle $io, InputInterface $input): int
    {
        $io->section('Processing Scheduled Cleanup');
        
        $dryRun = $input->getOption('dry-run');
        $table = $input->getOption('table');

        if ($table) {
            $io->text(sprintf('Processing table: %s', $table));
        }

        // First, mark inactive users
        $io->text('Marking inactive users...');
        $inactiveUsers = $this->retentionService->markInactiveUsers();
        $io->text(sprintf('Marked %d users as inactive', $inactiveUsers));

        // Process cleanup
        $summary = $this->retentionService->processScheduledCleanup($dryRun);

        $this->displayCleanupSummary($io, $summary);

        return empty($summary['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Process grace period cleanup (final deletions)
     */
    private function processGracePeriod(SymfonyStyle $io, InputInterface $input): int
    {
        $io->section('Processing Grace Period Cleanup');
        
        $dryRun = $input->getOption('dry-run');

        $summary = $this->retentionService->processGracePeriodCleanup($dryRun);

        $io->success(sprintf(
            'Processed %d records, deleted %d records',
            $summary['records_processed'],
            $summary['records_deleted']
        ));

        if (!empty($summary['errors'])) {
            $io->warning('Errors occurred during processing:');
            foreach ($summary['errors'] as $error) {
                $io->text('  - ' . $error);
            }
        }

        return empty($summary['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Clean up expired archives
     */
    private function cleanupArchives(SymfonyStyle $io, InputInterface $input): int
    {
        $io->section('Cleaning Up Expired Archives');
        
        $dryRun = $input->getOption('dry-run');
        $retentionDays = (int) ($input->getOption('days') ?: 2555); // 7 years default

        $io->text(sprintf('Archive retention period: %d days', $retentionDays));

        $summary = $this->archiver->cleanupExpiredArchives($retentionDays, $dryRun);

        $io->success(sprintf('Deleted %d expired archive records', $summary['deleted']));

        if (!empty($summary['errors'])) {
            $io->warning('Errors occurred during cleanup:');
            foreach ($summary['errors'] as $error) {
                $io->text('  - ' . $error);
            }
        }

        return empty($summary['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Generate retention report
     */
    private function generateReport(SymfonyStyle $io, InputInterface $input): int
    {
        $io->section('Generating Retention Report');
        
        $days = (int) $input->getOption('days');
        $format = $input->getOption('format');
        $outputFile = $input->getOption('output');

        // Get cleanup statistics
        $cleanupStats = $this->retentionService->getCleanupStatistics($days);
        $archiveStats = $this->archiver->getArchiveStatistics($days);

        // Display statistics
        $this->displayStatistics($io, $cleanupStats, $archiveStats, $days);

        // Generate detailed report if output file specified
        if ($outputFile) {
            $reportData = [
                'period_days' => $days,
                'generated_at' => new DateTime(),
                'cleanup_statistics' => $cleanupStats,
                'archive_statistics' => $archiveStats,
                'policies' => $this->retentionService->getActivePolicies()
            ];

            switch ($format) {
                case 'json':
                    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
                    break;
                    
                case 'csv':
                    $this->generateCsvReport($reportData, $outputFile);
                    break;
                    
                default:
                    $this->generateTextReport($reportData, $outputFile);
                    break;
            }

            $io->success(sprintf('Report saved to: %s', $outputFile));
        }

        return Command::SUCCESS;
    }

    /**
     * Show current status
     */
    private function showStatus(SymfonyStyle $io, InputInterface $input): int
    {
        $io->section('Current Retention Status');

        // Show active policies
        $policies = $this->retentionService->getActivePolicies();
        $io->text(sprintf('Active retention policies: %d', count($policies)));

        $policyTable = [];
        foreach ($policies as $policy) {
            $policyTable[] = [
                $policy['policy_name'],
                $policy['table_name'],
                $policy['retention_period_days'] . ' days',
                $policy['deletion_action'],
                $policy['enabled'] ? 'Yes' : 'No'
            ];
        }

        $io->table(
            ['Policy Name', 'Table', 'Retention Period', 'Action', 'Enabled'],
            $policyTable
        );

        // Show expired records count
        $expiredRecords = $this->retentionService->findExpiredRecords();
        $totalExpired = array_sum(array_map('count', $expiredRecords));

        $io->text(sprintf('Records eligible for cleanup: %d', $totalExpired));

        if ($totalExpired > 0) {
            $io->text('Breakdown by table:');
            foreach ($expiredRecords as $table => $records) {
                $io->text(sprintf('  - %s: %d records', $table, count($records)));
            }
        }

        // Show recent job statistics
        $stats = $this->retentionService->getCleanupStatistics(7);
        $io->text(sprintf('Cleanup jobs (last 7 days): %d completed, %d failed', 
            $stats['completed_jobs'], 
            $stats['failed_jobs']
        ));

        return Command::SUCCESS;
    }

    /**
     * Display cleanup summary
     */
    private function displayCleanupSummary(SymfonyStyle $io, array $summary): void
    {
        $io->success(sprintf(
            'Cleanup completed: %d jobs processed',
            $summary['jobs_processed']
        ));

        $summaryTable = [
            ['Records deleted', $summary['records_deleted']],
            ['Records anonymized', $summary['records_anonymized']],
            ['Records archived', $summary['records_archived']],
            ['Total processed', $summary['records_deleted'] + $summary['records_anonymized'] + $summary['records_archived']]
        ];

        $io->table(['Action', 'Count'], $summaryTable);

        if (!empty($summary['errors'])) {
            $io->warning(sprintf('%d errors occurred during cleanup:', count($summary['errors'])));
            foreach (array_slice($summary['errors'], 0, 10) as $error) {
                $io->text('  - ' . $error);
            }
            
            if (count($summary['errors']) > 10) {
                $io->text(sprintf('  ... and %d more errors', count($summary['errors']) - 10));
            }
        }
    }

    /**
     * Display statistics
     */
    private function displayStatistics(SymfonyStyle $io, array $cleanupStats, array $archiveStats, int $days): void
    {
        $io->text(sprintf('Statistics for the last %d days:', $days));
        
        // Cleanup statistics
        $io->section('Cleanup Jobs');
        $cleanupTable = [
            ['Total jobs', $cleanupStats['total_jobs']],
            ['Completed jobs', $cleanupStats['completed_jobs']],
            ['Failed jobs', $cleanupStats['failed_jobs']],
            ['Records processed', $cleanupStats['total_records_processed']],
            ['Records deleted', $cleanupStats['total_records_deleted']],
            ['Records anonymized', $cleanupStats['total_records_anonymized']],
            ['Records archived', $cleanupStats['total_records_archived']]
        ];
        $io->table(['Metric', 'Value'], $cleanupTable);

        // Archive statistics
        $io->section('Archives');
        $archiveTable = [
            ['Total archived', $archiveStats['total_archived']],
            ['Total size', $this->formatBytes($archiveStats['total_size_bytes'])]
        ];
        
        if (!empty($archiveStats['by_type'])) {
            foreach ($archiveStats['by_type'] as $type => $count) {
                $archiveTable[] = [ucfirst(str_replace('_', ' ', $type)), $count];
            }
        }
        
        $io->table(['Metric', 'Value'], $archiveTable);
    }

    /**
     * Generate CSV report
     */
    private function generateCsvReport(array $data, string $filePath): void
    {
        $fp = fopen($filePath, 'w');
        
        fputcsv($fp, ['Generated At', $data['generated_at']->format('Y-m-d H:i:s')]);
        fputcsv($fp, ['Period (days)', $data['period_days']]);
        fputcsv($fp, []);
        
        // Cleanup statistics
        fputcsv($fp, ['Cleanup Statistics']);
        fputcsv($fp, ['Metric', 'Value']);
        foreach ($data['cleanup_statistics'] as $key => $value) {
            fputcsv($fp, [ucfirst(str_replace('_', ' ', $key)), $value]);
        }
        
        fputcsv($fp, []);
        
        // Archive statistics
        fputcsv($fp, ['Archive Statistics']);
        fputcsv($fp, ['Metric', 'Value']);
        foreach ($data['archive_statistics'] as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    fputcsv($fp, [ucfirst(str_replace('_', ' ', $key)) . ' - ' . $subKey, $subValue]);
                }
            } else {
                fputcsv($fp, [ucfirst(str_replace('_', ' ', $key)), $value]);
            }
        }
        
        fclose($fp);
    }

    /**
     * Generate text report
     */
    private function generateTextReport(array $data, string $filePath): void
    {
        $report = sprintf(
            "MultiFlexi Data Retention Report\n" .
            "Generated: %s\n" .
            "Period: %d days\n\n" .
            "CLEANUP STATISTICS\n" .
            "==================\n" .
            "Total jobs: %d\n" .
            "Completed jobs: %d\n" .
            "Failed jobs: %d\n" .
            "Records processed: %d\n" .
            "Records deleted: %d\n" .
            "Records anonymized: %d\n" .
            "Records archived: %d\n\n" .
            "ARCHIVE STATISTICS\n" .
            "==================\n" .
            "Total archived: %d\n" .
            "Total size: %s\n\n",
            $data['generated_at']->format('Y-m-d H:i:s'),
            $data['period_days'],
            $data['cleanup_statistics']['total_jobs'],
            $data['cleanup_statistics']['completed_jobs'],
            $data['cleanup_statistics']['failed_jobs'],
            $data['cleanup_statistics']['total_records_processed'],
            $data['cleanup_statistics']['total_records_deleted'],
            $data['cleanup_statistics']['total_records_anonymized'],
            $data['cleanup_statistics']['total_records_archived'],
            $data['archive_statistics']['total_archived'],
            $this->formatBytes($data['archive_statistics']['total_size_bytes'])
        );

        // Add active policies
        $report .= "ACTIVE POLICIES\n";
        $report .= "===============\n";
        foreach ($data['policies'] as $policy) {
            $report .= sprintf(
                "- %s (%s): %d days → %s\n",
                $policy['policy_name'],
                $policy['table_name'],
                $policy['retention_period_days'],
                $policy['deletion_action']
            );
        }

        file_put_contents($filePath, $report);
    }

    /**
     * Format bytes to human-readable size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
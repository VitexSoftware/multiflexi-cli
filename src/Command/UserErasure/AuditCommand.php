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

namespace MultiFlexi\Cli\Command\UserErasure;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AuditCommand extends Command
{
    protected static $defaultName = 'user-erasure:audit';

    protected function configure(): void
    {
        $this
            ->setName('user-erasure:audit')
            ->setDescription('Show audit trail for a GDPR user data erasure request')
            ->addOption('request-id', 'r', InputOption::VALUE_REQUIRED, 'Deletion request ID')
            ->addOption('export-audit', 'e', InputOption::VALUE_OPTIONAL, 'Export audit trail to file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $requestId = $input->getOption('request-id');

        if (!$requestId) {
            $io->error('--request-id is required');

            return self::FAILURE;
        }

        $auditLogger = new \MultiFlexi\DataErasure\DeletionAuditLogger();
        $auditTrail = $auditLogger->getAuditTrail($requestId);

        if (empty($auditTrail)) {
            $io->info('No audit trail found for this request.');

            return self::SUCCESS;
        }

        $tableData = [];

        foreach ($auditTrail as $entry) {
            $tableData[] = [
                substr($entry['performed_date'], 0, 19),
                $entry['table_name'],
                $entry['record_id'] ?: 'N/A',
                $entry['action'],
                \strlen($entry['reason']) > 40 ? substr($entry['reason'], 0, 37).'...' : $entry['reason'],
                $entry['performed_by_user_id'],
            ];
        }

        $io->title("Audit Trail for Request #{$requestId}");
        $io->table(['Date', 'Table', 'Record ID', 'Action', 'Reason', 'Performed By'], $tableData);

        $verification = $auditLogger->verifyAuditTrailIntegrity($requestId);

        if ($verification['complete']) {
            $io->success("Audit trail is complete ({$verification['entry_count']} entries)");
        } else {
            $io->warning('Audit trail has issues:');

            foreach ($verification['issues'] as $issue) {
                $io->text("  - {$issue}");
            }
        }

        $exportFile = $input->getOption('export-audit');

        if ($exportFile) {
            $csvContent = $auditLogger->exportAuditTrailAsCsv($requestId);

            if (file_put_contents($exportFile, $csvContent) !== false) {
                $io->success("Audit trail exported to: {$exportFile}");
            } else {
                $io->error("Failed to export audit trail to: {$exportFile}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}

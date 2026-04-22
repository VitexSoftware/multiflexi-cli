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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanupCommand extends Command
{
    protected static $defaultName = 'user-erasure:cleanup';

    protected function configure(): void
    {
        $this->setName('user-erasure:cleanup')->setDescription('Clean up old GDPR audit logs (7-year retention)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $retentionDays = 2555;

        $io->title('Cleaning Up Old Audit Logs');
        $io->text("Retention period: {$retentionDays} days (7 years)");

        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval("P{$retentionDays}D"));
        $io->text('Will delete audit logs older than: '.$cutoffDate->format('Y-m-d H:i:s'));

        if (!$io->confirm('Do you want to proceed with cleanup?')) {
            $io->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $auditLogger = new \MultiFlexi\DataErasure\DeletionAuditLogger();
        $deletedCount = $auditLogger->cleanupOldAuditLogs($retentionDays);

        if ($deletedCount > 0) {
            $io->success("Cleaned up {$deletedCount} old audit log entries.");
        } else {
            $io->info('No old audit log entries found to clean up.');
        }

        return self::SUCCESS;
    }
}

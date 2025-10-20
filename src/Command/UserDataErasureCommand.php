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

namespace MultiFlexi\Cli\Command;

use MultiFlexi\DataErasure\UserDataEraser;
use MultiFlexi\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * GDPR User Data Erasure CLI Command
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class UserDataErasureCommand extends Command
{
    protected static $defaultName = 'user:data-erasure';

    protected function configure(): void
    {
        $this
            ->setDescription('Manage GDPR user data erasure requests')
            ->setHelp('This command allows you to create, approve, and process user data erasure requests under GDPR Article 17')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: list, create, approve, reject, process, audit, cleanup')
            ->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'User ID for the operation')
            ->addOption('user-login', 'l', InputOption::VALUE_REQUIRED, 'User login for the operation')
            ->addOption('request-id', 'r', InputOption::VALUE_REQUIRED, 'Deletion request ID')
            ->addOption('deletion-type', 't', InputOption::VALUE_REQUIRED, 'Deletion type: soft, hard, anonymize', 'soft')
            ->addOption('reason', null, InputOption::VALUE_REQUIRED, 'Reason for the deletion request')
            ->addOption('notes', null, InputOption::VALUE_REQUIRED, 'Review notes for approval/rejection')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force operation without confirmation')
            ->addOption('export-audit', 'e', InputOption::VALUE_OPTIONAL, 'Export audit trail to file')
            ->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Filter requests by status: pending, approved, rejected, completed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        try {
            switch ($action) {
                case 'list':
                    return $this->listDeletionRequests($input, $io);
                case 'create':
                    return $this->createDeletionRequest($input, $io);
                case 'approve':
                    return $this->approveDeletionRequest($input, $io);
                case 'reject':
                    return $this->rejectDeletionRequest($input, $io);
                case 'process':
                    return $this->processDeletionRequest($input, $io);
                case 'audit':
                    return $this->showAuditTrail($input, $io);
                case 'cleanup':
                    return $this->cleanupAuditLogs($input, $io);
                default:
                    $io->error("Unknown action: {$action}");
                    $io->text('Available actions: list, create, approve, reject, process, audit, cleanup');
                    return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error("Error: " . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function listDeletionRequests(InputInterface $input, SymfonyStyle $io): int
    {
        $status = $input->getOption('status');
        
        $requests = new \Ease\SQL\Orm();
        $requests->setMyTable('user_deletion_requests');
        
        $query = $requests->listingQuery()
            ->select([
                'udr.*',
                'u.login as target_user_login',
                'u.firstname as target_user_firstname', 
                'u.lastname as target_user_lastname',
                'ru.login as requested_by_login',
                'rev.login as reviewed_by_login'
            ])
            ->join('user u', 'u.id = udr.user_id')
            ->join('user ru', 'ru.id = udr.requested_by_user_id')
            ->leftJoin('user rev', 'rev.id = udr.reviewed_by_user_id')
            ->orderBy('udr.request_date DESC');

        if ($status) {
            $query->where('udr.status', $status);
        }

        $requestList = $query->fetchAll();

        if (empty($requestList)) {
            $statusText = $status ? " with status '{$status}'" : '';
            $io->info("No deletion requests found{$statusText}.");
            return Command::SUCCESS;
        }

        $tableData = [];
        foreach ($requestList as $request) {
            $userName = trim($request['target_user_firstname'] . ' ' . $request['target_user_lastname']);
            if (empty($userName)) {
                $userName = 'N/A';
            }
            
            $tableData[] = [
                $request['id'],
                $request['target_user_login'],
                $userName,
                $request['deletion_type'],
                $request['status'],
                substr($request['request_date'], 0, 16), // Trim seconds
                $request['requested_by_login'],
                $request['reviewed_by_login'] ?: 'N/A'
            ];
        }

        $io->title('User Deletion Requests');
        $io->table(
            ['ID', 'User Login', 'User Name', 'Type', 'Status', 'Request Date', 'Requested By', 'Reviewed By'],
            $tableData
        );

        return Command::SUCCESS;
    }

    private function createDeletionRequest(InputInterface $input, SymfonyStyle $io): int
    {
        $userId = $input->getOption('user-id');
        $userLogin = $input->getOption('user-login');
        $deletionType = $input->getOption('deletion-type');
        $reason = $input->getOption('reason') ?: '';

        if (!$userId && !$userLogin) {
            $io->error('Either --user-id or --user-login must be specified');
            return Command::FAILURE;
        }

        if (!in_array($deletionType, ['soft', 'hard', 'anonymize'])) {
            $io->error('Invalid deletion type. Must be: soft, hard, or anonymize');
            return Command::FAILURE;
        }

        // Load target user
        $targetUser = new User($userId ?: $userLogin);
        if (!$targetUser->getId()) {
            $io->error('User not found');
            return Command::FAILURE;
        }

        // Check if user can request deletion
        $canRequest = UserDataEraser::canRequestDeletion($targetUser);
        if (!$canRequest['allowed']) {
            $io->error($canRequest['reason']);
            return Command::FAILURE;
        }

        // Use system user as requesting user for CLI operations
        $requestingUser = User::singleton();
        if (!$requestingUser->getId()) {
            $io->error('No authenticated user found for CLI operations');
            return Command::FAILURE;
        }

        $io->title('Creating Deletion Request');
        $io->definitionList(
            ['Target User' => $targetUser->getUserName() . ' (' . $targetUser->getDataValue('login') . ')'],
            ['Deletion Type' => $deletionType],
            ['Reason' => $reason ?: 'N/A'],
            ['Requesting User' => $requestingUser->getUserName()]
        );

        if (!$input->getOption('force')) {
            if (!$io->confirm('Do you want to proceed?')) {
                $io->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $eraser = new UserDataEraser($targetUser, $requestingUser);
        $requestId = $eraser->createDeletionRequest($deletionType, $reason);

        $io->success("Deletion request created with ID: {$requestId}");
        
        if ($deletionType === 'soft') {
            $io->info('Soft deletion requests can be processed immediately.');
            if ($io->confirm('Process this request now?')) {
                return $this->processRequestById($requestId, $io);
            }
        } else {
            $io->info('This request requires admin approval before processing.');
            $io->text('Use: multiflexi-cli user:data-erasure approve --request-id ' . $requestId);
        }

        return Command::SUCCESS;
    }

    private function approveDeletionRequest(InputInterface $input, SymfonyStyle $io): int
    {
        $requestId = $input->getOption('request-id');
        $notes = $input->getOption('notes') ?: '';

        if (!$requestId) {
            $io->error('--request-id is required');
            return Command::FAILURE;
        }

        $reviewer = User::singleton();
        if (!$reviewer->getId()) {
            $io->error('No authenticated user found for review operations');
            return Command::FAILURE;
        }
        
        // Load request details
        $request = new \Ease\SQL\Orm();
        $request->setMyTable('user_deletion_requests');
        $request->loadFromSQL($requestId);

        if (!$request->getId()) {
            $io->error('Deletion request not found');
            return Command::FAILURE;
        }

        if ($request->getDataValue('status') !== 'pending') {
            $io->warning("Request status is '{$request->getDataValue('status')}', not 'pending'");
            if (!$io->confirm('Continue anyway?')) {
                return Command::SUCCESS;
            }
        }

        $targetUser = new User($request->getDataValue('user_id'));
        $eraser = new UserDataEraser($targetUser, $reviewer);

        $io->title('Approving Deletion Request');
        $io->definitionList(
            ['Request ID' => $requestId],
            ['Target User' => $targetUser->getUserName()],
            ['Deletion Type' => $request->getDataValue('deletion_type')],
            ['Current Status' => $request->getDataValue('status')],
            ['Review Notes' => $notes ?: 'N/A']
        );

        if (!$input->getOption('force')) {
            if (!$io->confirm('Do you want to approve this deletion request?')) {
                $io->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        if ($eraser->approveDeletionRequest($requestId, $reviewer, $notes)) {
            $io->success('Deletion request approved successfully.');
            
            if ($io->confirm('Process this request now?')) {
                return $this->processRequestById($requestId, $io);
            }
        } else {
            $io->error('Failed to approve deletion request.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function rejectDeletionRequest(InputInterface $input, SymfonyStyle $io): int
    {
        $requestId = $input->getOption('request-id');
        $reason = $input->getOption('reason') ?: 'Request rejected by administrator';

        if (!$requestId) {
            $io->error('--request-id is required');
            return Command::FAILURE;
        }

        $reviewer = User::singleton();
        if (!$reviewer->getId()) {
            $io->error('No authenticated user found for review operations');
            return Command::FAILURE;
        }
        
        // Load request details
        $request = new \Ease\SQL\Orm();
        $request->setMyTable('user_deletion_requests');
        $request->loadFromSQL($requestId);

        if (!$request->getId()) {
            $io->error('Deletion request not found');
            return Command::FAILURE;
        }

        $targetUser = new User($request->getDataValue('user_id'));
        $eraser = new UserDataEraser($targetUser, $reviewer);

        $io->title('Rejecting Deletion Request');
        $io->definitionList(
            ['Request ID' => $requestId],
            ['Target User' => $targetUser->getUserName()],
            ['Current Status' => $request->getDataValue('status')],
            ['Rejection Reason' => $reason]
        );

        if (!$input->getOption('force')) {
            if (!$io->confirm('Do you want to reject this deletion request?')) {
                $io->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        if ($eraser->rejectDeletionRequest($requestId, $reviewer, $reason)) {
            $io->success('Deletion request rejected successfully.');
        } else {
            $io->error('Failed to reject deletion request.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function processDeletionRequest(InputInterface $input, SymfonyStyle $io): int
    {
        $requestId = $input->getOption('request-id');

        if (!$requestId) {
            $io->error('--request-id is required');
            return Command::FAILURE;
        }

        return $this->processRequestById($requestId, $io);
    }

    private function processRequestById(int $requestId, SymfonyStyle $io): int
    {
        // Load request details
        $request = new \Ease\SQL\Orm();
        $request->setMyTable('user_deletion_requests');
        $request->loadFromSQL($requestId);

        if (!$request->getId()) {
            $io->error('Deletion request not found');
            return Command::FAILURE;
        }

        $targetUser = new User($request->getDataValue('user_id'));
        $processingUser = User::singleton();
        
        if (!$processingUser->getId()) {
            $io->error('No authenticated user found for processing operations');
            return Command::FAILURE;
        }

        $eraser = new UserDataEraser($targetUser, $processingUser);

        $io->title('Processing Deletion Request');
        $io->definitionList(
            ['Request ID' => $requestId],
            ['Target User' => $targetUser->getUserName()],
            ['Deletion Type' => $request->getDataValue('deletion_type')],
            ['Status' => $request->getDataValue('status')]
        );

        if ($request->getDataValue('status') === 'completed') {
            $io->warning('This request has already been completed.');
            return Command::SUCCESS;
        }

        if ($request->getDataValue('deletion_type') === 'hard') {
            $io->warning('⚠️  HARD DELETION WARNING ⚠️');
            $io->text([
                'This operation will PERMANENTLY DELETE user data and cannot be undone.',
                'This includes:',
                '  - User account and personal information',
                '  - Associated companies (if not shared)',
                '  - Run templates (if not shared)',
                '  - Job history (anonymized)',
                '',
                'Audit logs will be retained for legal compliance.'
            ]);
        } elseif ($request->getDataValue('deletion_type') === 'anonymize') {
            $io->warning('This operation will anonymize all personal data for this user.');
            $io->text('The user account will be disabled but data structures will be preserved.');
        }

        if (!$io->confirm('Do you want to proceed with processing this deletion request?', false)) {
            $io->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $progressBar = $io->createProgressBar();
        $progressBar->setMessage('Starting deletion process...');
        $progressBar->start();

        try {
            $result = $eraser->processDeletionRequest($requestId, false); // Skip admin approval check for CLI
            
            $progressBar->setMessage('Deletion process completed');
            $progressBar->finish();
            $io->newLine(2);

            if ($result) {
                $io->success('✅ Deletion request processed successfully.');
                
                // Show audit trail
                if ($io->confirm('Show audit trail?')) {
                    $this->showAuditTrailForRequest($requestId, $io);
                }
            } else {
                $io->error('❌ Failed to process deletion request.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $progressBar->finish();
            $io->newLine();
            $io->error("Processing failed: " . $e->getMessage());
            
            if ($io->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function showAuditTrail(InputInterface $input, SymfonyStyle $io): int
    {
        $requestId = $input->getOption('request-id');
        $exportFile = $input->getOption('export-audit');

        if (!$requestId) {
            $io->error('--request-id is required');
            return Command::FAILURE;
        }

        $this->showAuditTrailForRequest($requestId, $io);

        if ($exportFile) {
            $auditLogger = new \MultiFlexi\DataErasure\DeletionAuditLogger();
            $csvContent = $auditLogger->exportAuditTrailAsCsv($requestId);
            
            if (file_put_contents($exportFile, $csvContent) !== false) {
                $io->success("Audit trail exported to: {$exportFile}");
            } else {
                $io->error("Failed to export audit trail to: {$exportFile}");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    private function showAuditTrailForRequest(int $requestId, SymfonyStyle $io): void
    {
        $auditLogger = new \MultiFlexi\DataErasure\DeletionAuditLogger();
        $auditTrail = $auditLogger->getAuditTrail($requestId);

        if (empty($auditTrail)) {
            $io->info('No audit trail found for this request.');
            return;
        }

        $tableData = [];
        foreach ($auditTrail as $entry) {
            $tableData[] = [
                substr($entry['performed_date'], 0, 19), // Trim microseconds
                $entry['table_name'],
                $entry['record_id'] ?: 'N/A',
                $entry['action'],
                $this->truncateText($entry['reason'], 40),
                $entry['performed_by_user_id']
            ];
        }

        $io->title("Audit Trail for Request #{$requestId}");
        $io->table(
            ['Date', 'Table', 'Record ID', 'Action', 'Reason', 'Performed By'],
            $tableData
        );

        // Show integrity verification
        $verification = $auditLogger->verifyAuditTrailIntegrity($requestId);
        if ($verification['complete']) {
            $io->success("✅ Audit trail is complete ({$verification['entry_count']} entries)");
        } else {
            $io->warning("⚠️  Audit trail has issues:");
            foreach ($verification['issues'] as $issue) {
                $io->text("  - {$issue}");
            }
        }
    }

    private function cleanupAuditLogs(InputInterface $input, SymfonyStyle $io): int
    {
        $retentionDays = 2555; // 7 years default
        
        $io->title('Cleaning Up Old Audit Logs');
        $io->text("Retention period: {$retentionDays} days (7 years)");
        
        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval("P{$retentionDays}D"));
        $io->text("Will delete audit logs older than: " . $cutoffDate->format('Y-m-d H:i:s'));
        
        if (!$io->confirm('Do you want to proceed with cleanup?')) {
            $io->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $auditLogger = new \MultiFlexi\DataErasure\DeletionAuditLogger();
        $deletedCount = $auditLogger->cleanupOldAuditLogs($retentionDays);

        if ($deletedCount > 0) {
            $io->success("🧹 Cleaned up {$deletedCount} old audit log entries.");
        } else {
            $io->info('No old audit log entries found to clean up.');
        }
        
        return Command::SUCCESS;
    }

    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        return substr($text, 0, $maxLength - 3) . '...';
    }
}
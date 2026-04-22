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

use MultiFlexi\DataErasure\UserDataEraser;
use MultiFlexi\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RejectCommand extends Command
{
    protected static $defaultName = 'user-erasure:reject';

    protected function configure(): void
    {
        $this
            ->setName('user-erasure:reject')
            ->setDescription('Reject a GDPR user data erasure request')
            ->addOption('request-id', 'r', InputOption::VALUE_REQUIRED, 'Deletion request ID')
            ->addOption('reason', null, InputOption::VALUE_REQUIRED, 'Reason for rejection')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force operation without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $requestId = $input->getOption('request-id');
        $reason = $input->getOption('reason') ?: 'Request rejected by administrator';

        if (!$requestId) {
            $io->error('--request-id is required');

            return self::FAILURE;
        }

        $reviewer = User::singleton();

        if (!$reviewer->getId()) {
            $io->error('No authenticated user found for review operations');

            return self::FAILURE;
        }

        $request = new \Ease\SQL\Orm();
        $request->setMyTable('user_deletion_requests');
        $request->loadFromSQL($requestId);

        if (!$request->getId()) {
            $io->error('Deletion request not found');

            return self::FAILURE;
        }

        $targetUser = new User($request->getDataValue('user_id'));
        $eraser = new UserDataEraser($targetUser, $reviewer);

        $io->title('Rejecting Deletion Request');
        $io->definitionList(
            ['Request ID' => $requestId],
            ['Target User' => $targetUser->getUserName()],
            ['Current Status' => $request->getDataValue('status')],
            ['Rejection Reason' => $reason],
        );

        if (!$input->getOption('force') && !$io->confirm('Do you want to reject this deletion request?')) {
            $io->info('Operation cancelled.');

            return self::SUCCESS;
        }

        if ($eraser->rejectDeletionRequest($requestId, $reviewer, $reason)) {
            $io->success('Deletion request rejected successfully.');
        } else {
            $io->error('Failed to reject deletion request.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

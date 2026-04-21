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

class ApproveCommand extends Command
{
    protected static $defaultName = 'user-erasure:approve';

    protected function configure(): void
    {
        $this
            ->setDescription('Approve a GDPR user data erasure request')
            ->addOption('request-id', 'r', InputOption::VALUE_REQUIRED, 'Deletion request ID')
            ->addOption('notes', null, InputOption::VALUE_REQUIRED, 'Review notes')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force operation without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $requestId = $input->getOption('request-id');
        $notes = $input->getOption('notes') ?: '';

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

        if ($request->getDataValue('status') !== 'pending') {
            $io->warning("Request status is '{$request->getDataValue('status')}', not 'pending'");

            if (!$io->confirm('Continue anyway?')) {
                return self::SUCCESS;
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
            ['Review Notes' => $notes ?: 'N/A'],
        );

        if (!$input->getOption('force') && !$io->confirm('Do you want to approve this deletion request?')) {
            $io->info('Operation cancelled.');

            return self::SUCCESS;
        }

        if ($eraser->approveDeletionRequest($requestId, $reviewer, $notes)) {
            $io->success('Deletion request approved successfully.');
        } else {
            $io->error('Failed to approve deletion request.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

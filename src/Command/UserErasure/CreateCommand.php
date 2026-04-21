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

class CreateCommand extends Command
{
    protected static $defaultName = 'user-erasure:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create a GDPR user data erasure request')
            ->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('user-login', 'l', InputOption::VALUE_REQUIRED, 'User login')
            ->addOption('deletion-type', 't', InputOption::VALUE_REQUIRED, 'Deletion type: soft, hard, anonymize', 'soft')
            ->addOption('reason', null, InputOption::VALUE_REQUIRED, 'Reason for the deletion request')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force operation without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getOption('user-id');
        $userLogin = $input->getOption('user-login');
        $deletionType = $input->getOption('deletion-type');
        $reason = $input->getOption('reason') ?: '';

        if (!$userId && !$userLogin) {
            $io->error('Either --user-id or --user-login must be specified');

            return self::FAILURE;
        }

        if (!\in_array($deletionType, ['soft', 'hard', 'anonymize'], true)) {
            $io->error('Invalid deletion type. Must be: soft, hard, or anonymize');

            return self::FAILURE;
        }

        $targetUser = new User($userId ?: $userLogin);

        if (!$targetUser->getId()) {
            $io->error('User not found');

            return self::FAILURE;
        }

        $canRequest = UserDataEraser::canRequestDeletion($targetUser);

        if (!$canRequest['allowed']) {
            $io->error($canRequest['reason']);

            return self::FAILURE;
        }

        $requestingUser = User::singleton();

        if (!$requestingUser->getId()) {
            $io->error('No authenticated user found for CLI operations');

            return self::FAILURE;
        }

        $io->title('Creating Deletion Request');
        $io->definitionList(
            ['Target User' => $targetUser->getUserName().' ('.$targetUser->getDataValue('login').')'],
            ['Deletion Type' => $deletionType],
            ['Reason' => $reason ?: 'N/A'],
            ['Requesting User' => $requestingUser->getUserName()],
        );

        if (!$input->getOption('force') && !$io->confirm('Do you want to proceed?')) {
            $io->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $eraser = new UserDataEraser($targetUser, $requestingUser);
        $requestId = $eraser->createDeletionRequest($deletionType, $reason);

        $io->success("Deletion request created with ID: {$requestId}");

        return self::SUCCESS;
    }
}

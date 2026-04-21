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

class ProcessCommand extends Command
{
    protected static $defaultName = 'user-erasure:process';

    protected function configure(): void
    {
        $this
            ->setDescription('Process an approved GDPR user data erasure request')
            ->addOption('request-id', 'r', InputOption::VALUE_REQUIRED, 'Deletion request ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $requestId = $input->getOption('request-id');

        if (!$requestId) {
            $io->error('--request-id is required');

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
        $processingUser = User::singleton();

        if (!$processingUser->getId()) {
            $io->error('No authenticated user found for processing operations');

            return self::FAILURE;
        }

        $eraser = new UserDataEraser($targetUser, $processingUser);

        $io->title('Processing Deletion Request');
        $io->definitionList(
            ['Request ID' => $requestId],
            ['Target User' => $targetUser->getUserName()],
            ['Deletion Type' => $request->getDataValue('deletion_type')],
            ['Status' => $request->getDataValue('status')],
        );

        if ($request->getDataValue('status') === 'completed') {
            $io->warning('This request has already been completed.');

            return self::SUCCESS;
        }

        if (!$io->confirm('Do you want to proceed with processing this deletion request?', false)) {
            $io->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $progressBar = $io->createProgressBar();
        $progressBar->start();

        try {
            $result = $eraser->processDeletionRequest($requestId, false);
            $progressBar->finish();
            $io->newLine(2);

            if ($result) {
                $io->success('Deletion request processed successfully.');
            } else {
                $io->error('Failed to process deletion request.');

                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $progressBar->finish();
            $io->newLine();
            $io->error('Processing failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

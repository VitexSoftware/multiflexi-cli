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

class ListCommand extends Command
{
    protected static $defaultName = 'user-erasure:list';

    protected function configure(): void
    {
        $this
            ->setDescription('List GDPR user data erasure requests')
            ->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Filter by status: pending, approved, rejected, completed')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)')
            ->addOption('format', 'F', InputOption::VALUE_REQUIRED, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
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
                'rev.login as reviewed_by_login',
            ])
            ->join('user u', 'u.id = udr.user_id')
            ->join('user ru', 'ru.id = udr.requested_by_user_id')
            ->leftJoin('user rev', 'rev.id = udr.reviewed_by_user_id')
            ->orderBy('udr.request_date DESC');

        if ($status) {
            $query->where('udr.status', $status);
        }

        $order = $input->getOption('order');

        if (!empty($order)) {
            $query = $query->orderBy('udr.id '.(strtoupper($order) === 'D' ? 'DESC' : 'ASC'));
        }

        $limit = $input->getOption('limit');

        if (!empty($limit) && is_numeric($limit)) {
            $query = $query->limit((int) $limit);
        }

        $requestList = $query->fetchAll();

        if (empty($requestList)) {
            $statusText = $status ? " with status '{$status}'" : '';
            $io->info("No deletion requests found{$statusText}.");

            return self::SUCCESS;
        }

        $tableData = [];

        foreach ($requestList as $request) {
            $userName = trim($request['target_user_firstname'].' '.$request['target_user_lastname']);

            if (empty($userName)) {
                $userName = 'N/A';
            }

            $tableData[] = [
                $request['id'],
                $request['target_user_login'],
                $userName,
                $request['deletion_type'],
                $request['status'],
                substr($request['request_date'], 0, 16),
                $request['requested_by_login'],
                $request['reviewed_by_login'] ?: 'N/A',
            ];
        }

        $io->title('User Deletion Requests');
        $io->table(
            ['ID', 'User Login', 'User Name', 'Type', 'Status', 'Request Date', 'Requested By', 'Reviewed By'],
            $tableData,
        );

        return self::SUCCESS;
    }
}

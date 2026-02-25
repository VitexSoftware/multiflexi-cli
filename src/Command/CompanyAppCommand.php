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

use MultiFlexi\Application;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompanyAppCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'companyapp';

    protected function configure(): void
    {
        $this
            ->setDescription('Manage company-application relations')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|create|update|delete')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Relation ID')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'Application UUID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for list action (skip N results)')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        if ($action === 'list') {
            $companyId = $input->getOption('company_id');
            $appId = $input->getOption('app_id');
            $appUuid = $input->getOption('app_uuid');

            if (empty($companyId) || (empty($appId) && empty($appUuid))) {
                $output->writeln('<error>--company_id and either --app_id or --app_uuid are required for listing runtemplates.</error>');

                return Command::FAILURE;
            }

            if (!empty($appUuid)) {
                $app = new Application();
                $found = $app->listingQuery()->where(['uuid' => $appUuid])->fetch();

                if (!$found) {
                    $output->writeln('<error>No application found with given UUID</error>');

                    return Command::FAILURE;
                }

                $appId = $found['id'];
            }

            $runTemplate = new RunTemplate();
            $query = $runTemplate->listingQuery()->where([
                'company_id' => $companyId,
                'app_id' => $appId,
            ]);

            // Handle order option
            $order = $input->getOption('order');

            if (!empty($order)) {
                $orderBy = strtoupper($order) === 'D' ? 'DESC' : 'ASC';
                $query = $query->orderBy('id '.$orderBy);
            }

            // Handle limit option
            $limit = $input->getOption('limit');

            if (!empty($limit) && is_numeric($limit)) {
                $query = $query->limit((int) $limit);
            }

            // Handle offset option
            $offset = $input->getOption('offset');

            if (!empty($offset) && is_numeric($offset)) {
                $query = $query->offset((int) $offset);
            }

            $runtemplates = $query->fetchAll();

            if ($format === 'json') {
                $output->writeln(json_encode($runtemplates, \JSON_PRETTY_PRINT));
            } else {
                $output->writeln(self::outputTable($runtemplates));
            }

            return Command::SUCCESS;
        }

        // TODO: Implement logic for get, create, update, delete
        $output->writeln('<info>companyapp command is not yet implemented for this action.</info>');

        return Command::SUCCESS;
    }
}

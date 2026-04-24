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

namespace MultiFlexi\Cli\Command\CompanyApp;

use MultiFlexi\Application;
use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'company-app:list';

    protected function configure(): void
    {
        $this
            ->setName('company-app:list')
            ->setDescription('List run templates for a company+app combination')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'Application UUID')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for pagination')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of fields to display');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $companyId = $input->getOption('company_id');
        $appId = $input->getOption('app_id');
        $appUuid = $input->getOption('app_uuid');

        if (empty($companyId) || (empty($appId) && empty($appUuid))) {
            $output->writeln('<error>--company_id and either --app_id or --app_uuid are required.</error>');

            return self::FAILURE;
        }

        if (!empty($appUuid)) {
            $found = (new Application())->listingQuery()->where(['uuid' => $appUuid])->fetch();

            if (!$found) {
                $output->writeln('<error>No application found with given UUID</error>');

                return self::FAILURE;
            }

            $appId = $found['id'];
        }

        $query = (new RunTemplate())->listingQuery()->where(['company_id' => $companyId, 'app_id' => $appId]);

        $order = $input->getOption('order');

        if (!empty($order)) {
            $query = $query->orderBy('id '.(strtoupper($order) === 'D' ? 'DESC' : 'ASC'));
        }

        $limit = $input->getOption('limit');

        if (!empty($limit) && is_numeric($limit)) {
            $query = $query->limit((int) $limit);
        }

        $offset = $input->getOption('offset');

        if (!empty($offset) && is_numeric($offset)) {
            $query = $query->offset((int) $offset);
        }

        $runtemplates = $query->fetchAll();

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $runtemplates = array_map(static function ($rt) use ($fieldList) {
                return array_intersect_key($rt, array_flip($fieldList));
            }, $runtemplates);
        }

        if ($format === 'json') {
            $output->writeln(json_encode($runtemplates, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(self::outputTable($runtemplates));
        }

        return self::SUCCESS;
    }
}

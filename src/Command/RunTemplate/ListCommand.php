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

namespace MultiFlexi\Cli\Command\RunTemplate;

use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:list';

    protected function configure(): void
    {
        $this
            ->setName('run-template:list')
            ->setDescription('List run templates')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('company', null, InputOption::VALUE_REQUIRED, 'Filter by company slug or ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'Filter by application UUID')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $rt = new RunTemplate();
        $query = $rt->listingQuery();

        $companyOption = $input->getOption('company');

        if ($companyOption) {
            $query->join('company ON company.id = runtemplate.company_id');

            if (is_numeric($companyOption)) {
                $query->where('company.id', (int) $companyOption);
            } else {
                $query->where('company.slug', $companyOption);
            }
        }

        $appUuid = $input->getOption('app_uuid');

        if ($appUuid) {
            $query->join('apps ON apps.id = runtemplate.app_id');
            $query->where('apps.uuid', $appUuid);
        }

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

        $rts = $query->fetchAll();

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $rts = array_map(static fn ($rt) => array_intersect_key($rt, array_flip($fieldList)), $rts);
        }

        if ($format === 'json') {
            $output->writeln(json_encode($rts, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(self::outputTable($rts));
        }

        return self::SUCCESS;
    }
}

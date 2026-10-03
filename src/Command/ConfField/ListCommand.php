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

namespace MultiFlexi\Cli\Command\ConfField;

use MultiFlexi\Conffield;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends BaseCommand
{
    protected static $defaultName = 'conffield:list';

    protected function configure(): void
    {
        $this
            ->setName('conffield:list')
            ->setDescription('List configuration fields (optionally filtered by application)')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'Application ID (optional; omit to list all)')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for pagination')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of fields to include');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $appIdRaw = $input->getOption('app_id');
        $appId = $appIdRaw === null || $appIdRaw === '' ? 0 : (int) $appIdRaw;

        if ($appIdRaw !== null && $appIdRaw !== '' && $appId <= 0) {
            $msg = 'Missing or invalid --app_id';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $query = (new Conffield())->listingQuery();

        if ($appId > 0) {
            $query = $query->where('app_id', $appId);
        }

        $order = $input->getOption('order');

        if (!empty($order)) {
            $query = $query->orderBy('id '.(strtoupper((string) $order) === 'D' ? 'DESC' : 'ASC'));
        } elseif ($appId > 0) {
            $query = $query->orderBy('keyname ASC');
        } else {
            $query = $query->orderBy('app_id ASC, keyname ASC');
        }

        $limit = $input->getOption('limit');

        if (!empty($limit) && is_numeric($limit)) {
            $query = $query->limit((int) $limit);
        }

        $offset = $input->getOption('offset');

        if (!empty($offset) && is_numeric($offset)) {
            $query = $query->offset((int) $offset);
        }

        $rows = $query->fetchAll();

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', (string) $fields));
            $rows = array_map(static fn ($r) => array_intersect_key($r, array_flip($fieldList)), $rows);
        }

        if ($format === 'json') {
            $output->writeln(json_encode(array_values($rows), \JSON_PRETTY_PRINT));
        } else {
            if (empty($rows)) {
                $output->writeln($appId > 0
                    ? '<info>No configuration fields for this application.</info>'
                    : '<info>No configuration fields found.</info>');
            } else {
                $output->writeln(self::outputTable($rows));
            }
        }

        return self::SUCCESS;
    }
}

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

namespace MultiFlexi\Cli\Command\Company;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Company;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'company:list';

    protected function configure(): void
    {
        $this
            ->setName('company:list')
            ->setDescription('List companies')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for results')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of fields to include in output')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $query = (new Company())->listingQuery();

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

        $companies = $query->fetchAll();

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $companies = array_map(static fn ($c) => array_intersect_key($c, array_flip($fieldList)), $companies);
        }

        if ($format === 'json') {
            $output->writeln(json_encode($companies, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(self::outputTable($companies));
        }

        return self::SUCCESS;
    }
}

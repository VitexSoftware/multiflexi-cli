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

namespace MultiFlexi\Cli\Command\EventRule;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\EventRule;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-rule:list';

    protected function configure(): void
    {
        $this
            ->setDescription('List event rules')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('event_source_id', null, InputOption::VALUE_REQUIRED, 'Filter by Event Source ID')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $query = (new EventRule())->listingQuery();

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

        $sourceId = $input->getOption('event_source_id');

        if (!empty($sourceId)) {
            $query = $query->where('event_source_id', (int) $sourceId);
        }

        $rules = $query->fetchAll();

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $rules = array_map(static fn ($r) => array_intersect_key($r, array_flip($fieldList)), $rules);
        }

        if ($format === 'json') {
            $output->writeln(json_encode($rules, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln($this->outputTable($rules));
        }

        return self::SUCCESS;
    }
}

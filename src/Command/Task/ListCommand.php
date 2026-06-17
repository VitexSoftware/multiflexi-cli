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

namespace MultiFlexi\Cli\Command\Task;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'task:list';

    protected function configure(): void
    {
        $this
            ->setName('task:list')
            ->setDescription('List tasks')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'Filter by RunTemplate ID')
            ->addOption('state', null, InputOption::VALUE_REQUIRED, 'Filter by state: open, running, fulfilled, fulfilled_late, failed, missed')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for pagination')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of fields to include in output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $query = (new Task())->listingQuery();

        $runtemplateId = $input->getOption('runtemplate_id');

        if (!empty($runtemplateId) && is_numeric($runtemplateId)) {
            $query->where('runtemplate_id', (int) $runtemplateId);
        }

        $state = $input->getOption('state');

        if (!empty($state)) {
            $validStates = [Task::STATE_OPEN, Task::STATE_RUNNING, Task::STATE_FULFILLED, Task::STATE_FULFILLED_LATE, Task::STATE_FAILED, Task::STATE_MISSED];

            if (\in_array($state, $validStates, true)) {
                $query->where('state', $state);
            }
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

        $tasks = $query->fetchAll();

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $tasks = array_map(static fn ($task) => array_intersect_key($task, array_flip($fieldList)), $tasks);
        }

        if ($format === 'json') {
            $jsonResult = json_encode($tasks, \JSON_PRETTY_PRINT | \JSON_INVALID_UTF8_SUBSTITUTE);
            $output->writeln($jsonResult !== false ? $jsonResult : '[]');
        } else {
            if (empty($tasks)) {
                $output->writeln(_('No tasks found.'));

                return self::SUCCESS;
            }

            $output->writeln(self::outputTable($tasks));
        }

        return self::SUCCESS;
    }
}

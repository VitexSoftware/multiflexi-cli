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

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'task:get';

    protected function configure(): void
    {
        $this
            ->setName('task:get')
            ->setDescription('Get a task by ID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Task ID')
            ->addOption('with-jobs', null, InputOption::VALUE_NONE, 'Include job attempts in output')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of fields to display');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => _('Missing --id')], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>'._('Missing --id').'</error>');
            }

            return self::FAILURE;
        }

        $task = new Task((int) $id);
        $fields = $input->getOption('fields');

        if ($fields) {
            $fieldList = array_map('trim', explode(',', $fields));
            $data = array_filter($task->getData(), static fn ($key) => \in_array($key, $fieldList, true), \ARRAY_FILTER_USE_KEY);
        } else {
            $data = $task->getData();
        }

        if ($input->getOption('with-jobs')) {
            $data['jobs'] = $task->getJobs();
        }

        if ($format === 'json') {
            $jsonResult = json_encode($data, \JSON_PRETTY_PRINT | \JSON_INVALID_UTF8_SUBSTITUTE);
            $output->writeln($jsonResult !== false ? $jsonResult : '{}');
        } else {
            foreach ($data as $k => $v) {
                if (\is_array($v)) {
                    $output->writeln("{$k}:");

                    if (!empty($v)) {
                        $output->writeln(self::outputTable($v));
                    }
                } else {
                    $output->writeln("{$k}: {$v}");
                }
            }
        }

        return self::SUCCESS;
    }
}

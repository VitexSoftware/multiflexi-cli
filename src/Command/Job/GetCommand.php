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

namespace MultiFlexi\Cli\Command\Job;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Job;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'job:get';

    protected function configure(): void
    {
        $this
            ->setName('job:get')
            ->setDescription('Get a job by ID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Job ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing --id'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing --id</error>');
            }

            return self::FAILURE;
        }

        $job = new Job((int) $id);
        $fields = $input->getOption('fields');

        if ($fields) {
            $fieldsArray = explode(',', $fields);
            $data = array_filter($job->getData(), static fn ($key) => \in_array($key, $fieldsArray, true), \ARRAY_FILTER_USE_KEY);
        } else {
            $data = $job->getData();
        }

        if ($format === 'json') {
            $jsonResult = json_encode($data, \JSON_PRETTY_PRINT | \JSON_INVALID_UTF8_SUBSTITUTE);
            $output->writeln($jsonResult !== false ? $jsonResult : '{}');
        } else {
            foreach ($data as $k => $v) {
                $output->writeln("{$k}: {$v}");
            }
        }

        return self::SUCCESS;
    }
}

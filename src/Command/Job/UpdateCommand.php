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

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'job:update';

    protected function configure(): void
    {
        $this
            ->setName('job:update')
            ->setDescription('Update a job')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Job ID')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('scheduled', null, InputOption::VALUE_REQUIRED, 'Scheduled datetime')
            ->addOption('executor', null, InputOption::VALUE_REQUIRED, 'Executor')
            ->addOption('schedule_type', null, InputOption::VALUE_REQUIRED, 'Schedule type')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'App ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $data = [];

        foreach (['runtemplate_id', 'scheduled', 'executor', 'schedule_type', 'app_id'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        $job = new Job((int) $id);
        $job->updateToSQL($data, ['id' => $id]);

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = $job->getData();

            if ($format === 'json') {
                $jsonResult = json_encode($full, \JSON_PRETTY_PRINT | \JSON_INVALID_UTF8_SUBSTITUTE);
                $output->writeln($jsonResult !== false ? $jsonResult : '{}');
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['updated' => true, 'job_id' => $id], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Job updated successfully (ID: {$id})");
            }
        }

        return self::SUCCESS;
    }
}

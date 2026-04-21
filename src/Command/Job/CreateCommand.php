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

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'job:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create a job')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('scheduled', null, InputOption::VALUE_REQUIRED, 'Scheduled datetime')
            ->addOption('executor', null, InputOption::VALUE_REQUIRED, 'Executor')
            ->addOption('schedule_type', null, InputOption::VALUE_REQUIRED, 'Schedule type');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $runtemplateId = $input->getOption('runtemplate_id');
        $scheduled = $input->getOption('scheduled');

        if (empty($runtemplateId) || empty($scheduled)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing --runtemplate_id or --scheduled'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing --runtemplate_id or --scheduled</error>');
            }

            return self::FAILURE;
        }

        $env = new \MultiFlexi\ConfigFields('Job Env');
        $scheduledDT = new \DateTime($scheduled);
        $executor = $input->getOption('executor') ?? 'Native';
        $scheduleType = $input->getOption('schedule_type') ?? 'adhoc';
        $job = new Job();
        $jobId = $job->newJob(new \MultiFlexi\RunTemplate((int) $runtemplateId), $env, $scheduledDT, $executor, $scheduleType);

        if (!\is_int($jobId) || $jobId <= 0) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Failed to create job'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Failed to create job</error>');
            }

            return self::FAILURE;
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = $job->getData();
            $full['env'] = $job->getEnv();

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
                $output->writeln(json_encode(['job_id' => $jobId], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Job created with ID: {$jobId}");
            }
        }

        return self::SUCCESS;
    }
}

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

use MultiFlexi\Job;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:schedule';

    protected function configure(): void
    {
        $this
            ->setDescription('Schedule a run template for execution')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('schedule_time', null, InputOption::VALUE_OPTIONAL, 'Schedule time (Y-m-d H:i:s or "now")', 'now')
            ->addOption('executor', null, InputOption::VALUE_OPTIONAL, 'Executor')
            ->addOption('config', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Config key=value (repeatable)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $scheduleTime = $input->getOption('schedule_time') ?? 'now';
        $executor = $input->getOption('executor') ?? null;

        try {
            $rt = new RunTemplate(is_numeric($id) ? (int) $id : $id);

            if (empty($rt->getMyKey())) {
                $output->writeln('<error>RunTemplate not found</error>');

                return self::FAILURE;
            }

            if ((int) $rt->getDataValue('active') !== 1) {
                $output->writeln('<error>RunTemplate is not active. Scheduling forbidden.</error>');

                return self::FAILURE;
            }

            if ($executor === null) {
                $rtExecutor = $rt->getDataValue('executor');
                $executor = !empty($rtExecutor) ? $rtExecutor : 'Native';
            }

            $overridedEnv = $this->buildOverridedEnv($input);
            $jobber = new Job();
            $scheduleDateTime = new \DateTime($scheduleTime);
            $now = new \DateTime();
            $isImmediate = ($scheduleDateTime->getTimestamp() <= $now->getTimestamp() + 5);
            $scheduleType = $isImmediate ? 'adhoc' : 'cli';

            $jobber->prepareJob($rt, $overridedEnv, $scheduleDateTime, $executor, $scheduleType);

            if ($format === 'json') {
                $output->writeln(json_encode([
                    'runtemplate_id' => $id,
                    'scheduled' => $scheduleDateTime->format('Y-m-d H:i:s'),
                    'executor' => $executor,
                    'job_id' => $jobber->getMyKey(),
                ], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("RunTemplate {$id} scheduled for execution at ".$scheduleDateTime->format('Y-m-d H:i:s'));
                $output->writeln("Executor: {$executor}");
                $output->writeln("Job ID: {$jobber->getMyKey()}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to schedule run template: '.$e->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}

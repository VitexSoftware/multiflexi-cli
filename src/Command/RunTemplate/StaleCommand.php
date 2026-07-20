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

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Detects run-templates whose `next_schedule` is stuck in the past.
 *
 * Under normal operation `next_schedule` is only ever non-null for the short
 * window between a job being queued and its completion (see
 * MultiFlexi\Job::finish() and MultiFlexi\Scheduler::addJob()). If a job
 * never reaches finish() cleanly (crash, OOM-kill, executor failure outside
 * the normal fail path), the run-template is silently dropped from
 * CronScheduler::scheduleCronJobs() (which only considers next_schedule IS
 * NULL rows) until something calls Scheduler::initializeScheduling() to
 * reset it. This command surfaces that stuck state for external monitoring
 * (e.g. Zabbix) independent of whatever heals it internally.
 */
class StaleCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'run-template:stale';

    protected function configure(): void
    {
        $this
            ->setName('run-template:stale')
            ->setDescription('List active run-templates whose next_schedule is stuck in the past')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('tolerance-hours', null, InputOption::VALUE_REQUIRED, 'How many hours a non-null next_schedule may lag behind now before it is reported as stale', '6');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $toleranceHours = (float) $input->getOption('tolerance-hours');

        $rt = new RunTemplate();
        $threshold = (new \DateTime())->modify('-'.$toleranceHours.' hours')->format('Y-m-d H:i:s');

        $stale = $rt->listingQuery()
            ->where('runtemplate.active', true)
            ->where('runtemplate.interv != ?', 'n')
            ->where('runtemplate.next_schedule IS NOT NULL')
            ->where('runtemplate.next_schedule < ?', $threshold)
            ->select(['runtemplate.id', 'runtemplate.name', 'runtemplate.company_id', 'runtemplate.next_schedule', 'runtemplate.last_schedule'])
            ->fetchAll();

        if ($format === 'json') {
            $this->jsonSuccess($output, \sprintf('%d stale run-template(s) found', \count($stale)), ['count' => \count($stale), 'stale' => $stale]);
        } else {
            if (empty($stale)) {
                $output->writeln('No stale run-templates found.');
            } else {
                $output->writeln(self::outputTable($stale));
            }
        }

        return self::SUCCESS;
    }
}

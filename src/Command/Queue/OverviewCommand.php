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

namespace MultiFlexi\Cli\Command\Queue;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\ScheduleLister;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OverviewCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'queue:overview';

    protected function configure(): void
    {
        $this
            ->setDescription('Show comprehensive queue metrics overview')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $lister = new ScheduleLister();
        $totalJobsInQueue = $lister->listingQuery()->count();
        $rows = $lister->listingQuery()->fetchAll();

        $uniqueApps = !empty($rows) ? \count(array_unique(array_column($rows, 'app_id'))) : 0;
        $uniqueCompanies = !empty($rows) ? \count(array_unique(array_column($rows, 'company_id'))) : 0;
        $uniqueRuntemplates = !empty($rows) ? \count(array_unique(array_column($rows, 'runtemplate_id'))) : 0;

        $now = new \DateTime();
        $today = $now->format('Y-m-d');
        $tomorrow = (clone $now)->add(new \DateInterval('P1D'))->format('Y-m-d');
        $thisWeek = (clone $now)->add(new \DateInterval('P7D'))->format('Y-m-d');
        $thisMonth = (clone $now)->add(new \DateInterval('P30D'))->format('Y-m-d');

        $jobsToday = 0;
        $jobsTomorrow = 0;
        $jobsThisWeek = 0;
        $jobsThisMonth = 0;
        $overdueJobs = 0;

        foreach ($rows as $row) {
            if (!empty($row['after'])) {
                $afterDate = substr($row['after'], 0, 10);

                if ($afterDate < $today) {
                    ++$overdueJobs;
                } elseif ($afterDate === $today) {
                    ++$jobsToday;
                } elseif ($afterDate === $tomorrow) {
                    ++$jobsTomorrow;
                }

                if ($afterDate <= $thisWeek && $afterDate >= $today) {
                    ++$jobsThisWeek;
                }

                if ($afterDate <= $thisMonth && $afterDate >= $today) {
                    ++$jobsThisMonth;
                }
            }
        }

        $orphanedJobsCount = (new \MultiFlexi\Job())->listingQuery()
            ->where('begin IS NULL')
            ->where('exitcode IS NULL')
            ->where('id NOT IN (SELECT job FROM schedule WHERE job IS NOT NULL)')
            ->count();

        if ($format === 'json') {
            $output->writeln(json_encode([
                'comprehensive_queue_metrics' => [
                    'total_jobs_in_queue' => $totalJobsInQueue,
                    'orphaned_jobs' => $orphanedJobsCount,
                    'unique_applications' => $uniqueApps,
                    'unique_companies' => $uniqueCompanies,
                    'unique_runtemplates' => $uniqueRuntemplates,
                    'schedule_breakdown' => [
                        'overdue_jobs' => $overdueJobs,
                        'jobs_today' => $jobsToday,
                        'jobs_tomorrow' => $jobsTomorrow,
                        'jobs_this_week' => $jobsThisWeek,
                        'jobs_this_month' => $jobsThisMonth,
                    ],
                ],
            ], \JSON_PRETTY_PRINT));
        } else {
            $metricsTable = [
                ['Total jobs in queue', $totalJobsInQueue],
                ['Orphaned jobs', $orphanedJobsCount],
                ['Unique applications', $uniqueApps],
                ['Unique companies', $uniqueCompanies],
                ['Unique runtemplates', $uniqueRuntemplates],
                ['', ''],
                ['Schedule Breakdown', ''],
                ['Overdue jobs (before today)', $overdueJobs],
                ['Jobs scheduled for today', $jobsToday],
                ['Jobs scheduled for tomorrow', $jobsTomorrow],
                ['Jobs within next 7 days', $jobsThisWeek],
                ['Jobs within next 30 days', $jobsThisMonth],
            ];
            $output->writeln(self::outputTable($metricsTable, 200, ['Metric', 'Value']));
        }

        return self::SUCCESS;
    }
}

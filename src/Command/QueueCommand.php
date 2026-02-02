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

namespace MultiFlexi\Cli\Command;

use MultiFlexi\ScheduleLister;
use MultiFlexi\Scheduler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'queue';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setName('queue')
            ->setDescription('Queue operations (list, truncate) - shows metric overview when no action specified')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: list|truncate (optional - shows overview if omitted)')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = $input->getArgument('action') ? strtolower($input->getArgument('action')) : 'overview';

        switch ($action) {
            case 'overview':
                $lister = new ScheduleLister();
                $query = $lister->listingQuery();

                // Get queue statistics
                $totalJobsInQueue = $lister->listingQuery()->count();
                $rows = $query->fetchAll();

                // Calculate comprehensive metrics
                $uniqueApps = !empty($rows) ? \count(array_unique(array_column($rows, 'app_id'))) : 0;
                $uniqueCompanies = !empty($rows) ? \count(array_unique(array_column($rows, 'company_id'))) : 0;
                $uniqueRuntemplates = !empty($rows) ? \count(array_unique(array_column($rows, 'runtemplate_id'))) : 0;

                // Count jobs by schedule timing
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
                $jobsIn2027 = 0;

                foreach ($rows as $row) {
                    if (!empty($row['after'])) {
                        $afterDate = substr($row['after'], 0, 10);
                        $afterYear = substr($row['after'], 0, 4);

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

                        if ($afterYear === '2027') {
                            ++$jobsIn2027;
                        }
                    }
                }

                if ($format === 'json') {
                    $result = [
                        'comprehensive_queue_metrics' => [
                            'total_jobs_in_queue' => $totalJobsInQueue,
                            'unique_applications' => $uniqueApps,
                            'unique_companies' => $uniqueCompanies,
                            'unique_runtemplates' => $uniqueRuntemplates,
                            'schedule_breakdown' => [
                                'overdue_jobs' => $overdueJobs,
                                'jobs_today' => $jobsToday,
                                'jobs_tomorrow' => $jobsTomorrow,
                                'jobs_this_week' => $jobsThisWeek,
                                'jobs_this_month' => $jobsThisMonth,
                                'jobs_in_2027' => $jobsIn2027,
                            ],
                        ],
                    ];
                    $output->writeln(json_encode($result, \JSON_PRETTY_PRINT));
                } else {
                    // Display comprehensive metric overview
                    $metricsTable = [
                        ['Total jobs in queue', $totalJobsInQueue],
                        ['Unique applications', $uniqueApps],
                        ['Unique companies', $uniqueCompanies],
                        ['Unique runtemplates', $uniqueRuntemplates],
                        ['', ''], // Empty row separator
                        ['Schedule Breakdown', ''],
                        ['Overdue jobs (before today)', $overdueJobs],
                        ['Jobs scheduled for today', $jobsToday],
                        ['Jobs scheduled for tomorrow', $jobsTomorrow],
                        ['Jobs within next 7 days', $jobsThisWeek],
                        ['Jobs within next 30 days', $jobsThisMonth],
                        ['Jobs scheduled for 2027', $jobsIn2027],
                    ];
                    $output->writeln(self::outputTable($metricsTable, 200, ['Metric', 'Value']));
                }

                return self::SUCCESS;
            case 'list':
                $lister = new ScheduleLister();
                $query = $lister->listingQuery();

                // Handle order option
                $order = $input->getOption('order');

                if (!empty($order)) {
                    $orderBy = strtoupper($order) === 'D' ? 'DESC' : 'ASC';
                    $query = $query->orderBy('id '.$orderBy);
                }

                // Handle limit option
                $limit = $input->getOption('limit');

                if (!empty($limit) && is_numeric($limit)) {
                    $query = $query->limit((int) $limit);
                }

                // Handle offset option
                $offset = $input->getOption('offset');

                if (!empty($offset) && is_numeric($offset)) {
                    $query = $query->offset((int) $offset);
                }

                $rows = $query->fetchAll();

                // For each row, populate missing fields by joining manually
                foreach ($rows as &$row) {
                    // Initialize missing fields with empty values
                    $row['schedule_type'] ??= '';
                    $row['runtemplate_name'] = '';
                    $row['runtemplate_id'] = '';
                    $row['app_name'] = '';
                    $row['app_id'] = '';
                    $row['company_name'] = '';
                    $row['company_id'] = '';

                    // If we have job ID, try to get related data
                    if (!empty($row['job'])) {
                        try {
                            $job = new \MultiFlexi\Job((int) $row['job']);
                            $runtimeTemplateId = $job->getDataValue('runtemplate_id');

                            if ($runtimeTemplateId) {
                                $runTemplate = new \MultiFlexi\RunTemplate((int) $runtimeTemplateId);
                                $row['runtemplate_name'] = $runTemplate->getDataValue('name') ?: '';
                                $row['runtemplate_id'] = $runtimeTemplateId;

                                $appId = $runTemplate->getDataValue('app_id');

                                if ($appId) {
                                    $app = new \MultiFlexi\Application((int) $appId);
                                    $row['app_name'] = $app->getDataValue('name') ?: '';
                                    $row['app_id'] = $appId;
                                }

                                $companyId = $runTemplate->getDataValue('company_id');

                                if ($companyId) {
                                    $company = new \MultiFlexi\Company((int) $companyId);
                                    $row['company_name'] = $company->getDataValue('name') ?: '';
                                    $row['company_id'] = $companyId;
                                }
                            }
                        } catch (\Exception $e) {
                            // Ignore errors when loading related data
                        }
                    }
                }

                // Handle fields option
                $fields = $input->getOption('fields');

                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $rows = array_map(static function ($row) use ($fieldList) {
                        return array_intersect_key($row, array_flip($fieldList));
                    }, $rows);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($rows, \JSON_PRETTY_PRINT));
                } else {
                    if (!empty($rows)) {
                        $output->writeln(self::outputTable($rows));
                    } else {
                        $output->writeln('No jobs in queue.');
                    }
                }

                return self::SUCCESS;
            case 'truncate':
                $scheduler = new Scheduler();
                $waiting = $scheduler->listingQuery()->count();
                $pdo = $scheduler->getFluentPDO()->getPdo();
                $table = $scheduler->getMyTable();
                // Check the database driver to use the appropriate truncate/delete command
                $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

                if ($driver === 'sqlite') {
                    // For SQLite, use DELETE FROM and reset the sequence if needed
                    $result = $pdo->exec('DELETE FROM '.$table);
                    // Reset AUTOINCREMENT sequence if table has one
                    $pdo->exec('DELETE FROM sqlite_sequence WHERE name="'.$table.'"');
                } else {
                    // For MySQL and others, use TRUNCATE TABLE
                    $result = $pdo->exec('TRUNCATE TABLE '.$table);
                }

                $pdo->exec('UPDATE runtemplate SET next_schedule=NULL');

                $msg = ($result !== false)
                    ? ("Queue truncated. Previously waiting jobs: {$waiting}.")
                    : 'Failed to truncate queue.';

                if ($format === 'json') {
                    $output->writeln(json_encode(['result' => $msg, 'waiting' => $waiting], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln("Jobs waiting before truncate: {$waiting}");
                    $output->writeln($msg);
                }

                return ($result !== false) ? self::SUCCESS : self::FAILURE;

            default:
                $output->writeln('<error>Unknown action for queue: '.$action.'</error>');

                return self::FAILURE;
        }
    }
}

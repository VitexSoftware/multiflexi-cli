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
    private ?string $postSortField = null;
    private ?string $postSortDirection = null;

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
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order field: "after", "id", "job", "schedule_type", "runtemplate_id", "runtemplate_name", "app_id", "app_name", "company_id", "company_name"')
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, 'Sort direction: "ASC", "DESC", "A", "D" (default: ASC)');
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
                $order = $input->getOption('order') ?: 'after'; // Default to 'after' if no order specified
                $direction = $input->getOption('direction');

                if (!empty($order)) {
                    $orderField = strtolower($order);
                    $orderBy = 'ASC'; // Default to ascending

                    // Check if direction is specified separately
                    if (!empty($direction)) {
                        $dir = strtoupper($direction);

                        if (\in_array($dir, ['DESC', 'D'], true)) {
                            $orderBy = 'DESC';
                        }
                    }

                    // Check if order has direction embedded (for backward compatibility)
                    if (str_contains($orderField, ' ')) {
                        $parts = explode(' ', $orderField);
                        $orderField = $parts[0];
                        $dir = strtoupper($parts[1]);

                        if (\in_array($dir, ['DESC', 'D'], true)) {
                            $orderBy = 'DESC';
                        }
                    }

                    // Map field names to actual database columns
                    switch ($orderField) {
                        case 'after':
                            $query = $query->orderBy('after '.$orderBy);

                            break;
                        case 'job':
                            $query = $query->orderBy('job '.$orderBy);

                            break;
                        case 'id':
                            // Backward compatibility: if order is just "D", treat as descending ID
                            if ($orderField === 'd') {
                                $orderBy = 'DESC';
                            }

                            $query = $query->orderBy('id '.$orderBy);

                            break;
                        case 'schedule_type':
                        case 'runtemplate_id':
                        case 'runtemplate_name':
                        case 'app_id':
                        case 'app_name':
                        case 'company_id':
                        case 'company_name':
                            // These fields need to be sorted after data population since they come from joins
                            $this->postSortField = $orderField;
                            $this->postSortDirection = $orderBy;

                            break;

                        default:
                            // Default to ID ordering
                            $query = $query->orderBy('id '.$orderBy);

                            break;
                    }
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
                    // Initialize missing fields with empty values in the desired order
                    $orderedRow = [
                        'id' => $row['id'] ?? '',
                        'job' => $row['job'] ?? '',
                        'schedule_type' => '',
                        'runtemplate_id' => '',
                        'runtemplate_name' => '',
                        'app_id' => '',
                        'app_name' => '',
                        'company_id' => '',
                        'company_name' => '',
                        'after' => $row['after'] ?? '',
                    ];

                    // Add human-readable waiting time to the "after" field
                    if (!empty($row['after'])) {
                        $scheduledTime = new \DateTime($row['after']);
                        $now = new \DateTime();
                        $interval = $now->diff($scheduledTime);

                        $waitingTime = '';

                        if ($scheduledTime < $now) {
                            $waitingTime = 'overdue ';
                        }

                        // Calculate total days including months and years
                        $totalDays = $interval->days;

                        if ($totalDays > 0) {
                            $waitingTime .= $totalDays.'d ';
                        }

                        if ($interval->h > 0) {
                            $waitingTime .= $interval->h.'h ';
                        }

                        if ($interval->i > 0) {
                            $waitingTime .= $interval->i.'m ';
                        }

                        if (empty(trim($waitingTime)) || ($totalDays === 0 && $interval->h === 0 && $interval->i === 0)) {
                            $waitingTime = 'now ';
                        }

                        $orderedRow['after'] = $row['after'].' ('.rtrim($waitingTime).')';
                    }

                    // If we have job ID, try to get related data
                    if (!empty($row['job'])) {
                        try {
                            $job = new \MultiFlexi\Job((int) $row['job']);
                            $runtimeTemplateId = $job->getDataValue('runtemplate_id');

                            if ($runtimeTemplateId) {
                                $runTemplate = new \MultiFlexi\RunTemplate((int) $runtimeTemplateId);
                                $orderedRow['runtemplate_name'] = $runTemplate->getDataValue('name') ?: '';
                                $orderedRow['runtemplate_id'] = $runtimeTemplateId;

                                // Get the interval code and convert it to readable schedule type
                                $intervalCode = $runTemplate->getDataValue('interv') ?: 'n';
                                $orderedRow['schedule_type'] = \MultiFlexi\Scheduler::codeToInterval($intervalCode);

                                $appId = $runTemplate->getDataValue('app_id');

                                if ($appId) {
                                    $app = new \MultiFlexi\Application((int) $appId);
                                    $orderedRow['app_name'] = $app->getDataValue('name') ?: '';
                                    $orderedRow['app_id'] = $appId;
                                }

                                $companyId = $runTemplate->getDataValue('company_id');

                                if ($companyId) {
                                    $company = new \MultiFlexi\Company((int) $companyId);
                                    $orderedRow['company_name'] = $company->getDataValue('name') ?: '';
                                    $orderedRow['company_id'] = $companyId;
                                }
                            }
                        } catch (\Exception $e) {
                            // Ignore errors when loading related data
                        }
                    }

                    // Replace the original row with the ordered version
                    $row = $orderedRow;
                }

                // Handle post-processing sort for fields that need data population first
                if (!empty($this->postSortField) && !empty($this->postSortDirection)) {
                    usort($rows, function ($a, $b) {
                        // Get values for comparison based on the field
                        switch ($this->postSortField) {
                            case 'schedule_type':
                                $aValue = $a['schedule_type'] ?? '';
                                $bValue = $b['schedule_type'] ?? '';

                                break;
                            case 'runtemplate_id':
                                $aValue = (int) ($a['runtemplate_id'] ?? 0);
                                $bValue = (int) ($b['runtemplate_id'] ?? 0);

                                break;
                            case 'runtemplate_name':
                                $aValue = $a['runtemplate_name'] ?? '';
                                $bValue = $b['runtemplate_name'] ?? '';

                                break;
                            case 'app_id':
                                $aValue = (int) ($a['app_id'] ?? 0);
                                $bValue = (int) ($b['app_id'] ?? 0);

                                break;
                            case 'app_name':
                                $aValue = $a['app_name'] ?? '';
                                $bValue = $b['app_name'] ?? '';

                                break;
                            case 'company_id':
                                $aValue = (int) ($a['company_id'] ?? 0);
                                $bValue = (int) ($b['company_id'] ?? 0);

                                break;
                            case 'company_name':
                                $aValue = $a['company_name'] ?? '';
                                $bValue = $b['company_name'] ?? '';

                                break;
                            case 'job':
                                $aValue = (int) ($a['job'] ?? 0);
                                $bValue = (int) ($b['job'] ?? 0);

                                break;

                            default:
                                return 0; // No comparison for unknown fields
                        }

                        // Compare values based on type
                        if (is_numeric($aValue) && is_numeric($bValue)) {
                            $result = $aValue <=> $bValue;
                        } else {
                            $result = strcasecmp((string) $aValue, (string) $bValue);
                        }

                        // Apply sort direction
                        if ($this->postSortDirection === 'DESC') {
                            $result = -$result;
                        }

                        return $result;
                    });
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

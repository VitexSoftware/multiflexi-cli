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

use MultiFlexi\Job;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of Job.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class JobCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'job';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    #[\Override]
    public function listing(): array
    {
        $engine = new self();

        return $engine->listingQuery()->select([
            'id',
        ])->fetchAll();
    }

    protected function configure(): void
    {
        $this
            ->setName('job')
            ->setDescription('Job operations')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->setHelp('This command manage Jobs')
            ->setDescription('Manage jobs')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: status|list|get|create|update|delete')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Job ID')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'Runtemplate ID')
            ->addOption('scheduled', null, InputOption::VALUE_REQUIRED, 'Scheduled datetime')
            ->addOption('executor', null, InputOption::VALUE_REQUIRED, 'Executor')
            ->addOption('schedule_type', null, InputOption::VALUE_REQUIRED, 'Schedule type')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'App ID')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)');
        // Add more options as needed
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'status':
                $engine = new \MultiFlexi\Engine();
                $pdo = $engine->getPdo();
                $database = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME).' '.
                        $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS).' '.
                        $pdo->getAttribute(\PDO::ATTR_SERVER_INFO).' '.
                        $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

                $queeLength = (new \MultiFlexi\Scheduler())->listingQuery()->count();

                // Query to get job status information
                $query = <<<'EOD'
                SELECT
                    COUNT(*) AS total_jobs,
                    SUM(CASE WHEN exitcode = 0 THEN 1 ELSE 0 END) AS successful_jobs,
                    SUM(CASE WHEN exitcode != 0 THEN 1 ELSE 0 END) AS failed_jobs,
                    SUM(CASE WHEN exitcode IS NULL THEN 1 ELSE 0 END) AS incomplete_jobs,
                    COUNT(DISTINCT app_id) AS total_applications,
                    SUM(CASE WHEN schedule IS NOT NULL THEN 1 ELSE 0 END) AS repeated_jobs
                FROM job
EOD;

                $stmt = $pdo->query($query);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                $status = [
                    'successful_jobs' => (int) $result['successful_jobs'],
                    'failed_jobs' => (int) $result['failed_jobs'],
                    'incomplete_jobs' => (int) $result['incomplete_jobs'],
                    'total_applications' => (int) $result['total_applications'],
                    'repeated_jobs' => (int) $result['repeated_jobs'],
                    'total_jobs' => (int) $result['total_jobs'],
                    'queue_length' => (int) $queeLength,
                ];

                $format = $input->getOption('format');

                if ($format === 'json') {
                    $output->writeln(json_encode($status, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln(self::outputTable($status));
                }

                return MultiFlexiCommand::SUCCESS;
            case 'list':
                $job = new Job();
                $query = $job->listingQuery();

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

                $jobs = $query->fetchAll();

                // Handle fields option
                $fields = $input->getOption('fields');

                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $jobs = array_map(static function ($job) use ($fieldList) {
                        return array_intersect_key($job, array_flip($fieldList));
                    }, $jobs);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($jobs, \JSON_PRETTY_PRINT));
                } else {
                    foreach ($jobs as $row) {
                        $output->writeln(implode(' | ', $row));
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'get':
                $id = $input->getOption('id');

                if (empty($id)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Missing --id for job get',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Missing --id for job get</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $job = new Job((int) $id);
                $fields = $input->getOption('fields');

                if ($fields) {
                    $fieldsArray = explode(',', $fields);
                    $filteredData = array_filter(
                        $job->getData(),
                        static fn ($key) => \in_array($key, $fieldsArray, true),
                        \ARRAY_FILTER_USE_KEY,
                    );

                    if ($format === 'json') {
                        $output->writeln(json_encode($filteredData, \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($filteredData as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                } else {
                    if ($format === 'json') {
                        $output->writeln(json_encode($job->getData(), \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($job->getData() as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'create':
                $runtemplateId = $input->getOption('runtemplate_id');
                $scheduled = $input->getOption('scheduled');

                if (empty($runtemplateId) || empty($scheduled)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Failed: Missing --runtemplate_id or --scheduled for job create',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Failed: Missing --runtemplate_id or --scheduled for job create</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $env = new \MultiFlexi\ConfigFields('Job Env');
                $scheduledDT = new \DateTime($scheduled);
                $executor = $input->getOption('executor') ?? 'Native';
                $scheduleType = $input->getOption('schedule_type') ?? 'adhoc';
                $job = new Job();
                $jobId = $job->newJob((int) $runtemplateId, $env, $scheduledDT, $executor, $scheduleType);

                // Only proceed if jobId is a valid positive integer
                if (!\is_int($jobId) || $jobId <= 0) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Failed to create job. Please check runtemplate_id and parameters.',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Failed to create job. Please check runtemplate_id and parameters.</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $full = $job->getData();
                    $full['env'] = $job->getEnv();

                    if ($format === 'json') {
                        $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
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

                return MultiFlexiCommand::SUCCESS;
            case 'update':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for job update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $job = new Job((int) $id);
                $data = [];

                foreach (['runtemplate_id', 'scheduled', 'executor', 'schedule_type', 'app_id'] as $field) {
                    $val = $input->getOption($field);

                    if ($val !== null) {
                        $data[$field] = $val;
                    }
                }

                if (empty($data)) {
                    $output->writeln('<error>No fields to update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $job->updateToSQL($data, ['id' => $id]);

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $full = $job->getData();

                    if ($format === 'json') {
                        $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
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

                return MultiFlexiCommand::SUCCESS;
            case 'delete':
                $id = $input->getOption('id');

                if (empty($id)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode(['error' => 'Missing --id for job delete'], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Missing --id for job delete</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $job = new Job((int) $id);
                $job->deleteFromSQL();

                if ($format === 'json') {
                    $output->writeln(json_encode(['deleted' => true, 'job_id' => $id], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln("Job deleted: ID={$id}");
                }

                return MultiFlexiCommand::SUCCESS;

            default:
                $output->writeln("<error>Unknown action: {$action}</error>");

                return MultiFlexiCommand::FAILURE;
        }
    }
}

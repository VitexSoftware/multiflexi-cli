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

class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'queue:list';
    private ?string $postSortField = null;
    private ?string $postSortDirection = null;

    protected function configure(): void
    {
        $this
            ->setName('queue:list')
            ->setDescription('List queued jobs')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for results')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of fields to include in output')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort field: after|id|job|schedule_type|runtemplate_id|runtemplate_name|app_id|app_name|company_id|company_name')
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, 'Sort direction: ASC or DESC (default: ASC)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $lister = new ScheduleLister();
        $query = $lister->listingQuery();

        $order = $input->getOption('order') ?: 'after';
        $direction = $input->getOption('direction');

        $orderField = strtolower($order);
        $orderBy = 'ASC';

        if (!empty($direction)) {
            $dir = strtoupper($direction);

            if (\in_array($dir, ['DESC', 'D'], true)) {
                $orderBy = 'DESC';
            }
        }

        if (str_contains($orderField, ' ')) {
            $parts = explode(' ', $orderField);
            $orderField = $parts[0];

            if (\in_array(strtoupper($parts[1]), ['DESC', 'D'], true)) {
                $orderBy = 'DESC';
            }
        }

        switch ($orderField) {
            case 'after':
                $query = $query->orderBy('after '.$orderBy);

                break;
            case 'job':
                $query = $query->orderBy('job '.$orderBy);

                break;
            case 'id':
                $query = $query->orderBy('id '.$orderBy);

                break;
            case 'schedule_type':
            case 'runtemplate_id':
            case 'runtemplate_name':
            case 'app_id':
            case 'app_name':
            case 'company_id':
            case 'company_name':
                $this->postSortField = $orderField;
                $this->postSortDirection = $orderBy;

                break;

            default:
                $query = $query->orderBy('id '.$orderBy);
        }

        $limit = $input->getOption('limit');

        if (!empty($limit) && is_numeric($limit)) {
            $query = $query->limit((int) $limit);
        }

        $offset = $input->getOption('offset');

        if (!empty($offset) && is_numeric($offset)) {
            $query = $query->offset((int) $offset);
        }

        $rows = $query->fetchAll();

        foreach ($rows as &$row) {
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

            if (!empty($row['after'])) {
                $scheduledTime = new \DateTime($row['after']);
                $now = new \DateTime();
                $interval = $now->diff($scheduledTime);
                $waitingTime = '';

                if ($scheduledTime < $now) {
                    $waitingTime = 'overdue ';
                }

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

            if (!empty($row['job'])) {
                try {
                    $job = new \MultiFlexi\Job((int) $row['job']);
                    $runtimeTemplateId = $job->getDataValue('runtemplate_id');

                    if ($runtimeTemplateId) {
                        $runTemplate = new \MultiFlexi\RunTemplate((int) $runtimeTemplateId);
                        $orderedRow['runtemplate_name'] = $runTemplate->getDataValue('name') ?: '';
                        $orderedRow['runtemplate_id'] = $runtimeTemplateId;
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
                    // Ignore errors loading related data
                }
            }

            $row = $orderedRow;
        }

        if (!empty($this->postSortField) && !empty($this->postSortDirection)) {
            $postSortField = $this->postSortField;
            $postSortDirection = $this->postSortDirection;
            usort($rows, static function ($a, $b) use ($postSortField, $postSortDirection) {
                $aValue = $a[$postSortField] ?? '';
                $bValue = $b[$postSortField] ?? '';

                if (is_numeric($aValue) && is_numeric($bValue)) {
                    $result = $aValue <=> $bValue;
                } else {
                    $result = strcasecmp((string) $aValue, (string) $bValue);
                }

                return $postSortDirection === 'DESC' ? -$result : $result;
            });
        }

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $rows = array_map(static fn ($row) => array_intersect_key($row, array_flip($fieldList)), $rows);
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
    }
}

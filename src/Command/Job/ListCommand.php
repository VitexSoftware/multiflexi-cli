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

class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'job:list';

    protected function configure(): void
    {
        $this
            ->setName('job:list')
            ->setDescription('List jobs')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $query = (new Job())->listingQuery();

        $order = $input->getOption('order');

        if (!empty($order)) {
            $query = $query->orderBy('id '.(strtoupper($order) === 'D' ? 'DESC' : 'ASC'));
        }

        $limit = $input->getOption('limit');

        if (!empty($limit) && is_numeric($limit)) {
            $query = $query->limit((int) $limit);
        }

        $offset = $input->getOption('offset');

        if (!empty($offset) && is_numeric($offset)) {
            $query = $query->offset((int) $offset);
        }

        $jobs = $query->fetchAll();

        foreach ($jobs as &$jobData) {
            if (isset($jobData['env']) && \Ease\Functions::isSerialized($jobData['env'])) {
                $envUnserialized = unserialize($jobData['env']);

                if ($envUnserialized instanceof \MultiFlexi\ConfigFields) {
                    $jobData['env'] = $envUnserialized->getEnvArray();
                } elseif (\is_array($envUnserialized)) {
                    $envArray = [];

                    foreach ($envUnserialized as $key => $envInfo) {
                        if (\is_array($envInfo) && isset($envInfo['value'])) {
                            $envArray[$key] = $envInfo['value'];
                        } elseif (\is_object($envInfo) && method_exists($envInfo, 'getValue')) {
                            $envArray[$key] = $envInfo->getValue();
                        }
                    }

                    $jobData['env'] = $envArray;
                }
            }
        }

        unset($jobData);

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $jobs = array_map(static fn ($job) => array_intersect_key($job, array_flip($fieldList)), $jobs);
        }

        if ($format === 'json') {
            $jsonResult = json_encode($jobs, \JSON_PRETTY_PRINT | \JSON_INVALID_UTF8_SUBSTITUTE);
            $output->writeln($jsonResult !== false ? $jsonResult : '[]');
        } else {
            foreach ($jobs as $row) {
                $displayRow = array_map(static fn ($value) => \is_array($value) ? json_encode($value) : $value, $row);
                $output->writeln(implode(' | ', $displayRow));
            }
        }

        return self::SUCCESS;
    }
}

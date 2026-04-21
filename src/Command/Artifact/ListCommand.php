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

namespace MultiFlexi\Cli\Command\Artifact;

use MultiFlexi\Artifact;
use MultiFlexi\Cli\Command\MultiFlexiCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'artifact:list';

    protected function configure(): void
    {
        $this
            ->setDescription('List job artifacts')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('job_id', null, InputOption::VALUE_REQUIRED, 'Filter by Job ID')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $query = (new Artifact())->listingQuery();

        $jobId = $input->getOption('job_id');

        if ($jobId !== null) {
            $query = $query->where('job_id = ?', [(int) $jobId]);
        }

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

        $artifacts = $query->fetchAll();

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $artifacts = array_map(static fn ($a) => array_intersect_key($a, array_flip($fieldList)), $artifacts);
        }

        if ($format === 'json') {
            $output->writeln(json_encode($artifacts, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(empty($artifacts) ? _('No artifacts found') : self::outputTable($artifacts));
        }

        return self::SUCCESS;
    }
}

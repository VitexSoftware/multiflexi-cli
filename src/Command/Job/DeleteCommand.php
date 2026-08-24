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

class DeleteCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'job:delete';

    protected function configure(): void
    {
        $this
            ->setName('job:delete')
            ->setDescription('Delete a job, or bulk-delete jobs matching filters (e.g. to clean up a scheduler-bug spike)')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Job ID (single delete)')
            ->addOption('runtemplate-id', null, InputOption::VALUE_REQUIRED, 'Bulk delete: restrict to this RunTemplate ID')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Bulk delete: only jobs scheduled/begun at or after this datetime (Y-m-d H:i:s)')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Bulk delete: only jobs scheduled/begun at or before this datetime (Y-m-d H:i:s)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Bulk delete: only count matching jobs, do not delete them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $runtemplateId = $input->getOption('runtemplate-id');
        $from = $input->getOption('from');
        $to = $input->getOption('to');

        if (!empty($id)) {
            (new Job((int) $id))->deleteFromSQL((int) $id);

            if ($format === 'json') {
                $output->writeln(json_encode(['deleted' => true, 'job_id' => $id], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Job deleted: ID={$id}");
            }

            return self::SUCCESS;
        }

        if (empty($runtemplateId) && empty($from) && empty($to)) {
            $message = 'Provide --id for a single job, or at least one of --runtemplate-id / --from / --to for a bulk delete';

            if ($format === 'json') {
                $output->writeln(json_encode(['error' => $message], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("<error>{$message}</error>");
            }

            return self::FAILURE;
        }

        $job = new Job();
        $query = $job->listingQuery();

        if (!empty($runtemplateId)) {
            $query->where('runtemplate_id', (int) $runtemplateId);
        }

        if (!empty($from)) {
            $query->where('begin >= ?', $from);
        }

        if (!empty($to)) {
            $query->where('begin <= ?', $to);
        }

        $jobIds = array_column($query->select('id', true)->fetchAll(), 'id');
        $deletedCount = 0;

        foreach ($jobIds as $jobId) {
            if (!$input->getOption('dry-run')) {
                (new Job())->deleteFromSQL((int) $jobId);
            }

            ++$deletedCount;
        }

        $dryRun = (bool) $input->getOption('dry-run');

        if ($format === 'json') {
            $output->writeln(json_encode([
                'dry_run' => $dryRun,
                'matched' => \count($jobIds),
                'deleted' => $dryRun ? 0 : $deletedCount,
                'job_ids' => $jobIds,
            ], \JSON_PRETTY_PRINT));
        } else {
            $verb = $dryRun ? 'Matched (dry-run, not deleted)' : 'Deleted';
            $output->writeln("{$verb}: ".\count($jobIds).' job(s)');
        }

        return self::SUCCESS;
    }
}

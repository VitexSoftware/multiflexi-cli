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
use MultiFlexi\Scheduler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'queue:fix';

    protected function configure(): void
    {
        $this
            ->setDescription('Fix queue by cleaning up orphaned jobs and broken records')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $scheduler = new Scheduler();
        $scheduler->cleanupOrphanedJobs();
        $scheduler->purgeBrokenQueueRecords();
        $scheduler->initializeScheduling();

        $jobModel = new \MultiFlexi\Job();
        $orphanedJobs = $jobModel->listingQuery()
            ->where('job.begin IS NULL')
            ->where('job.exitcode IS NULL')
            ->where('job.id NOT IN (SELECT job FROM schedule WHERE job IS NOT NULL)')
            ->fetchAll();

        foreach ($orphanedJobs as $orphan) {
            $orphanJob = new \MultiFlexi\Job((int) $orphan['id']);
            $orphanJob->deleteFromSQL();
            $scheduler->addStatusMessage(
                sprintf(
                    'Removed orphaned job #%d (runtemplate #%s, scheduled %s) - no schedule entry',
                    $orphan['id'],
                    $orphan['runtemplate_id'] ?? '?',
                    $orphan['schedule'] ?? '?',
                ),
                'info',
            );
        }

        $messages = [];

        foreach ($scheduler->getStatusMessages() as $message) {
            $messages[] = ['type' => $message->type, 'message' => $message->body];
        }

        if ($format === 'json') {
            $this->jsonSuccess($output, _('Queue diagnostics and fix completed'), ['messages' => $messages]);
        } else {
            if (empty($messages)) {
                $output->writeln(_('Nothing to clean up.'));
            } else {
                foreach ($messages as $message) {
                    $output->writeln(sprintf('[%s] %s', strtoupper($message['type']), $message['message']));
                }
            }

            $output->writeln('<info>'._('Queue diagnostics and fix completed').'</info>');
        }

        return self::SUCCESS;
    }
}

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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PruneCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'prune';
    protected static $defaultDescription = 'Prune logs and jobs, keeping only the latest N records (default: 1000)';

    protected function configure(): void
    {
        $this
            ->setName('prune')
            ->addOption('logs', null, InputOption::VALUE_NONE, 'Prune logs table')
            ->addOption('jobs', null, InputOption::VALUE_NONE, 'Prune jobs table')
            ->addOption('keep', null, InputOption::VALUE_OPTIONAL, 'Number of records to keep', 1000)
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $keep = (int) $input->getOption('keep');
        $format = strtolower((string) $input->getOption('format'));
        $doLogs = (bool) $input->getOption('logs');
        $doJobs = (bool) $input->getOption('jobs');

        if (!$doLogs && !$doJobs) {
            $message = 'No table specified. Use --logs and/or --jobs.';
            if ($format === 'json') {
                $this->jsonError($output, $message);
            } else {
                $output->writeln('<error>'.$message.'</error>');
            }

            return self::FAILURE;
        }

        $tables = [];

        if ($doLogs) {
            if ($format !== 'json') {
                $output->writeln("Pruning logs, keeping latest {$keep} records...");
            }
            $deleted = self::pruneTable('log', 'id', $keep);
            $tables['log'] = ['deleted' => $deleted, 'kept' => $keep];
            if ($format !== 'json') {
                $output->writeln("Deleted {$deleted} records from log.");
            }
        }

        if ($doJobs) {
            if ($format !== 'json') {
                $output->writeln("Pruning jobs, keeping latest {$keep} records...");
            }
            $deleted = self::pruneTable('job', 'id', $keep);
            $tables['job'] = ['deleted' => $deleted, 'kept' => $keep];
            if ($format !== 'json') {
                $output->writeln("Deleted {$deleted} records from job.");
            }
        }

        if ($format === 'json') {
            $this->jsonSuccess($output, 'Prune completed', [
                'keep' => $keep,
                'tables' => $tables,
            ]);
        } else {
            $output->writeln('<info>Prune completed.</info>');
        }

        return self::SUCCESS;
    }

    private static function pruneTable(string $table, string $idField, int $keep): int
    {
        $db = new \Ease\SQL\Engine();
        $sql = "DELETE FROM {$table} WHERE {$idField} NOT IN (SELECT {$idField} FROM (SELECT {$idField} FROM {$table} ORDER BY {$idField} DESC LIMIT {$keep}) as t)";
        $deleted = $db->getPdo()->exec($sql);

        return $deleted === false ? 0 : (int) $deleted;
    }
}

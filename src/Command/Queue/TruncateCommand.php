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

class TruncateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'queue:truncate';

    protected function configure(): void
    {
        $this
            ->setName('queue:truncate')
            ->setDescription('Truncate the job queue')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $scheduler = new Scheduler();
        $waiting = $scheduler->listingQuery()->count();
        $pdo = $scheduler->getFluentPDO()->getPdo();
        $table = $scheduler->getMyTable();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $result = $pdo->exec('DELETE FROM '.$table);
            $pdo->exec('DELETE FROM sqlite_sequence WHERE name="'.$table.'"');
        } else {
            $result = $pdo->exec('TRUNCATE TABLE '.$table);
        }

        $pdo->exec('UPDATE runtemplate SET next_schedule=NULL');

        $msg = ($result !== false)
            ? "Queue truncated. Previously waiting jobs: {$waiting}."
            : 'Failed to truncate queue.';

        if ($format === 'json') {
            $output->writeln(json_encode(['result' => $msg, 'waiting' => $waiting], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Jobs waiting before truncate: {$waiting}");
            $output->writeln($msg);
        }

        return ($result !== false) ? self::SUCCESS : self::FAILURE;
    }
}

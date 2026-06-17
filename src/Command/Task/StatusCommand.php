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

namespace MultiFlexi\Cli\Command\Task;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'task:status';

    protected function configure(): void
    {
        $this
            ->setName('task:status')
            ->setDescription('Show task aggregate statistics by state')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));

        $engine = new \MultiFlexi\Engine();
        $pdo = $engine->getPdo();

        $stmt = $pdo->query(<<<'EOD'
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN state = 'open'           THEN 1 ELSE 0 END) AS open,
                SUM(CASE WHEN state = 'running'        THEN 1 ELSE 0 END) AS running,
                SUM(CASE WHEN state = 'fulfilled'      THEN 1 ELSE 0 END) AS fulfilled,
                SUM(CASE WHEN state = 'fulfilled_late' THEN 1 ELSE 0 END) AS fulfilled_late,
                SUM(CASE WHEN state = 'failed'         THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN state = 'missed'         THEN 1 ELSE 0 END) AS missed
            FROM task
EOD);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $status = [
            'total' => (int) $row['total'],
            Task::STATE_OPEN => (int) $row['open'],
            Task::STATE_RUNNING => (int) $row['running'],
            Task::STATE_FULFILLED => (int) $row['fulfilled'],
            Task::STATE_FULFILLED_LATE => (int) $row['fulfilled_late'],
            Task::STATE_FAILED => (int) $row['failed'],
            Task::STATE_MISSED => (int) $row['missed'],
        ];

        if ($format === 'json') {
            $output->writeln(json_encode($status, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(self::outputTable([$status]));
        }

        return self::SUCCESS;
    }
}

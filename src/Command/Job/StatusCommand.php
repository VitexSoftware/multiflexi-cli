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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'job:status';

    protected function configure(): void
    {
        $this
            ->setDescription('Show job statistics')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));

        $engine = new \MultiFlexi\Engine();
        $pdo = $engine->getPdo();

        $queeLength = (new \MultiFlexi\Scheduler())->listingQuery()->count();

        $stmt = $pdo->query(<<<'EOD'
            SELECT
                COUNT(*) AS total_jobs,
                SUM(CASE WHEN exitcode = 0 THEN 1 ELSE 0 END) AS successful_jobs,
                SUM(CASE WHEN exitcode != 0 THEN 1 ELSE 0 END) AS failed_jobs,
                SUM(CASE WHEN exitcode IS NULL THEN 1 ELSE 0 END) AS incomplete_jobs,
                COUNT(DISTINCT app_id) AS total_applications,
                SUM(CASE WHEN schedule IS NOT NULL THEN 1 ELSE 0 END) AS repeated_jobs
            FROM job
EOD);
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

        if ($format === 'json') {
            $output->writeln(json_encode($status, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(self::outputTable($status));
        }

        return self::SUCCESS;
    }
}

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
            ->setDescription('Delete a job')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Job ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['error' => 'Missing --id'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing --id</error>');
            }

            return self::FAILURE;
        }

        (new Job((int) $id))->deleteFromSQL();

        if ($format === 'json') {
            $output->writeln(json_encode(['deleted' => true, 'job_id' => $id], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Job deleted: ID={$id}");
        }

        return self::SUCCESS;
    }
}

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

namespace MultiFlexi\Cli\Command\EventSource;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\EventSource;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-source:test';

    protected function configure(): void
    {
        $this
            ->setDescription('Test connectivity to an event source adapter database')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Event Source ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $source = new EventSource((int) $id, ['autoload' => true]);

        if ($source->isReachable()) {
            if ($format === 'json') {
                $output->writeln(json_encode(['reachable' => true], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<info>Connection to adapter database successful</info>');
            }

            return self::SUCCESS;
        }

        if ($format === 'json') {
            $output->writeln(json_encode(['reachable' => false], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('<error>Connection to adapter database failed</error>');
        }

        return self::FAILURE;
    }
}

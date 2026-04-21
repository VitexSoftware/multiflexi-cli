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

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-source:get';

    protected function configure(): void
    {
        $this
            ->setDescription('Get an event source by ID')
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

        $data = (new EventSource((int) $id))->getData();

        if ($format === 'json') {
            $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
        } else {
            foreach ($data as $k => $v) {
                $output->writeln("{$k}: {$v}");
            }
        }

        return self::SUCCESS;
    }
}

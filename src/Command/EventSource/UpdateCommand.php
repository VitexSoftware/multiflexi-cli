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

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-source:update';

    protected function configure(): void
    {
        $this
            ->setDescription('Update an event source')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Event Source ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Source name')
            ->addOption('adapter_type', null, InputOption::VALUE_REQUIRED, 'Adapter type')
            ->addOption('db_connection', null, InputOption::VALUE_REQUIRED, 'DB driver')
            ->addOption('db_host', null, InputOption::VALUE_REQUIRED, 'DB host')
            ->addOption('db_port', null, InputOption::VALUE_REQUIRED, 'DB port')
            ->addOption('db_database', null, InputOption::VALUE_REQUIRED, 'Database name')
            ->addOption('db_username', null, InputOption::VALUE_REQUIRED, 'DB username')
            ->addOption('db_password', null, InputOption::VALUE_REQUIRED, 'DB password')
            ->addOption('poll_interval', null, InputOption::VALUE_REQUIRED, 'Poll interval in seconds')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'Enabled (1|0)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $data = [];

        foreach (['name', 'adapter_type', 'db_connection', 'db_host', 'db_port', 'db_database', 'db_username', 'db_password', 'poll_interval', 'enabled'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        (new EventSource((int) $id))->updateToSQL($data, ['id' => $id]);

        if ($format === 'json') {
            $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('Event source updated successfully');
        }

        return self::SUCCESS;
    }
}

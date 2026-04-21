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

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-source:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create an event source')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Source name')
            ->addOption('adapter_type', null, InputOption::VALUE_REQUIRED, 'Adapter type')
            ->addOption('db_connection', null, InputOption::VALUE_REQUIRED, 'DB driver (mysql|pgsql|sqlite)', 'mysql')
            ->addOption('db_host', null, InputOption::VALUE_REQUIRED, 'DB host', 'localhost')
            ->addOption('db_port', null, InputOption::VALUE_REQUIRED, 'DB port', '3306')
            ->addOption('db_database', null, InputOption::VALUE_REQUIRED, 'Database name')
            ->addOption('db_username', null, InputOption::VALUE_REQUIRED, 'DB username')
            ->addOption('db_password', null, InputOption::VALUE_REQUIRED, 'DB password')
            ->addOption('poll_interval', null, InputOption::VALUE_REQUIRED, 'Poll interval in seconds', '60')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'Enabled (1|0)', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $name = $input->getOption('name');

        if (empty($name)) {
            $output->writeln('<error>Missing --name</error>');

            return self::FAILURE;
        }

        $source = new EventSource();
        $source->takeData([
            'name' => $name,
            'adapter_type' => $input->getOption('adapter_type') ?? '',
            'db_connection' => $input->getOption('db_connection'),
            'db_host' => $input->getOption('db_host'),
            'db_port' => $input->getOption('db_port'),
            'db_database' => $input->getOption('db_database') ?? '',
            'db_username' => $input->getOption('db_username') ?? '',
            'db_password' => $input->getOption('db_password') ?? '',
            'poll_interval' => (int) $input->getOption('poll_interval'),
            'enabled' => (int) $input->getOption('enabled'),
        ]);

        $id = $source->insertToSQL();

        if ($id) {
            if ($format === 'json') {
                $output->writeln(json_encode(['id' => $id, 'created' => true], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('Event source created with ID: '.$id);
            }

            return self::SUCCESS;
        }

        $output->writeln('<error>Failed to create event source</error>');

        return self::FAILURE;
    }
}

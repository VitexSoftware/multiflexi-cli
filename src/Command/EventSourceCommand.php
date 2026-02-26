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

use MultiFlexi\EventSource;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for managing EventSources.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class EventSourceCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'eventsource';

    protected function configure(): void
    {
        $this
            ->setName('eventsource')
            ->setDescription('Event source operations')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|create|update|remove|test')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Event Source ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Source name')
            ->addOption('adapter_type', null, InputOption::VALUE_REQUIRED, 'Adapter type (e.g. abraflexi-webhook-acceptor)')
            ->addOption('db_connection', null, InputOption::VALUE_REQUIRED, 'DB driver (mysql|pgsql|sqlite)', 'mysql')
            ->addOption('db_host', null, InputOption::VALUE_REQUIRED, 'DB host', 'localhost')
            ->addOption('db_port', null, InputOption::VALUE_REQUIRED, 'DB port', '3306')
            ->addOption('db_database', null, InputOption::VALUE_REQUIRED, 'Database name')
            ->addOption('db_username', null, InputOption::VALUE_REQUIRED, 'DB username')
            ->addOption('db_password', null, InputOption::VALUE_REQUIRED, 'DB password')
            ->addOption('poll_interval', null, InputOption::VALUE_REQUIRED, 'Poll interval in seconds', '60')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'Enabled (1|0)', '1')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)')
            ->setHelp('This command manages Event Sources (webhook adapter database connections)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'list':
                $source = new EventSource();
                $query = $source->listingQuery();

                $order = $input->getOption('order');

                if (!empty($order)) {
                    $orderBy = strtoupper($order) === 'D' ? 'DESC' : 'ASC';
                    $query = $query->orderBy('id '.$orderBy);
                }

                $limit = $input->getOption('limit');

                if (!empty($limit) && is_numeric($limit)) {
                    $query = $query->limit((int) $limit);
                }

                $offset = $input->getOption('offset');

                if (!empty($offset) && is_numeric($offset)) {
                    $query = $query->offset((int) $offset);
                }

                $sources = $query->fetchAll();

                $fields = $input->getOption('fields');

                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $sources = array_map(static function ($s) use ($fieldList) {
                        return array_intersect_key($s, array_flip($fieldList));
                    }, $sources);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($sources, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln($this->outputTable($sources));
                }

                return MultiFlexiCommand::SUCCESS;

            case 'get':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for eventsource get</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $source = new EventSource((int) $id);
                $data = $source->getData();

                if ($format === 'json') {
                    $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
                } else {
                    foreach ($data as $k => $v) {
                        $output->writeln("{$k}: {$v}");
                    }
                }

                return MultiFlexiCommand::SUCCESS;

            case 'create':
                $name = $input->getOption('name');

                if (empty($name)) {
                    $output->writeln('<error>Missing --name for eventsource create</error>');

                    return MultiFlexiCommand::FAILURE;
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
                        $output->writeln('Event Source created with ID: '.$id);
                    }

                    return MultiFlexiCommand::SUCCESS;
                }

                $output->writeln('<error>Failed to create Event Source</error>');

                return MultiFlexiCommand::FAILURE;

            case 'update':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for eventsource update</error>');

                    return MultiFlexiCommand::FAILURE;
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

                    return MultiFlexiCommand::FAILURE;
                }

                $source = new EventSource((int) $id);
                $source->updateToSQL($data, ['id' => $id]);

                if ($format === 'json') {
                    $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('Event Source updated successfully');
                }

                return MultiFlexiCommand::SUCCESS;

            case 'remove':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for eventsource remove</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $source = new EventSource();

                if ($source->deleteFromSQL((int) $id)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode(['removed' => true], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('Event Source removed successfully');
                    }

                    return MultiFlexiCommand::SUCCESS;
                }

                $output->writeln('<error>Failed to remove Event Source</error>');

                return MultiFlexiCommand::FAILURE;

            case 'test':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for eventsource test</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $source = new EventSource((int) $id, ['autoload' => true]);

                if ($source->isReachable()) {
                    if ($format === 'json') {
                        $output->writeln(json_encode(['reachable' => true], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<info>Connection to adapter database successful</info>');
                    }

                    return MultiFlexiCommand::SUCCESS;
                }

                if ($format === 'json') {
                    $output->writeln(json_encode(['reachable' => false], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>Connection to adapter database failed</error>');
                }

                return MultiFlexiCommand::FAILURE;

            default:
                $output->writeln("<error>Unknown action: {$action}</error>");

                return MultiFlexiCommand::FAILURE;
        }
    }
}

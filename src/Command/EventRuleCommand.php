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

use MultiFlexi\EventRule;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for managing EventRules.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class EventRuleCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'eventrule';

    protected function configure(): void
    {
        $this
            ->setName('eventrule')
            ->setDescription('Event rule operations')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|create|update|remove')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Event Rule ID')
            ->addOption('event_source_id', null, InputOption::VALUE_REQUIRED, 'Event Source ID')
            ->addOption('evidence', null, InputOption::VALUE_REQUIRED, 'Evidence pattern (e.g. faktura-vydana)')
            ->addOption('operation', null, InputOption::VALUE_REQUIRED, 'Operation: any|create|update|delete', 'any')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID to trigger')
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'Priority (higher = matched first)', '0')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'Enabled (1|0)', '1')
            ->addOption('env_mapping', null, InputOption::VALUE_REQUIRED, 'JSON env variable mapping')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)')
            ->setHelp('This command manages Event Rules (event-to-RunTemplate mappings)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'list':
                $rule = new EventRule();
                $query = $rule->listingQuery();

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

                $sourceId = $input->getOption('event_source_id');

                if (!empty($sourceId)) {
                    $query = $query->where('event_source_id', (int) $sourceId);
                }

                $rules = $query->fetchAll();

                $fields = $input->getOption('fields');

                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $rules = array_map(static function ($r) use ($fieldList) {
                        return array_intersect_key($r, array_flip($fieldList));
                    }, $rules);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($rules, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln($this->outputTable($rules));
                }

                return MultiFlexiCommand::SUCCESS;

            case 'get':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for eventrule get</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $rule = new EventRule((int) $id);
                $data = $rule->getData();

                if ($format === 'json') {
                    $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
                } else {
                    foreach ($data as $k => $v) {
                        $output->writeln("{$k}: {$v}");
                    }
                }

                return MultiFlexiCommand::SUCCESS;

            case 'create':
                $sourceId = $input->getOption('event_source_id');
                $runtemplateId = $input->getOption('runtemplate_id');

                if (empty($sourceId) || empty($runtemplateId)) {
                    $output->writeln('<error>Missing --event_source_id or --runtemplate_id for eventrule create</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $envMapping = $input->getOption('env_mapping');

                if (!empty($envMapping) && null === json_decode($envMapping, true)) {
                    $output->writeln('<error>Invalid JSON in --env_mapping</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $rule = new EventRule();
                $rule->takeData([
                    'event_source_id' => (int) $sourceId,
                    'evidence' => $input->getOption('evidence') ?? '',
                    'operation' => $input->getOption('operation'),
                    'runtemplate_id' => (int) $runtemplateId,
                    'priority' => (int) $input->getOption('priority'),
                    'enabled' => (int) $input->getOption('enabled'),
                    'env_mapping' => $envMapping ?? '{}',
                ]);

                $id = $rule->insertToSQL();

                if ($id) {
                    if ($format === 'json') {
                        $output->writeln(json_encode(['id' => $id, 'created' => true], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('Event Rule created with ID: '.$id);
                    }

                    return MultiFlexiCommand::SUCCESS;
                }

                $output->writeln('<error>Failed to create Event Rule</error>');

                return MultiFlexiCommand::FAILURE;

            case 'update':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for eventrule update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $data = [];

                foreach (['event_source_id', 'evidence', 'operation', 'runtemplate_id', 'priority', 'enabled', 'env_mapping'] as $field) {
                    $val = $input->getOption($field);

                    if ($val !== null) {
                        $data[$field] = $val;
                    }
                }

                if (isset($data['env_mapping']) && null === json_decode($data['env_mapping'], true)) {
                    $output->writeln('<error>Invalid JSON in --env_mapping</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                if (empty($data)) {
                    $output->writeln('<error>No fields to update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $rule = new EventRule((int) $id);
                $rule->updateToSQL($data, ['id' => $id]);

                if ($format === 'json') {
                    $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('Event Rule updated successfully');
                }

                return MultiFlexiCommand::SUCCESS;

            case 'remove':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for eventrule remove</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $rule = new EventRule();

                if ($rule->deleteFromSQL((int) $id)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode(['removed' => true], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('Event Rule removed successfully');
                    }

                    return MultiFlexiCommand::SUCCESS;
                }

                $output->writeln('<error>Failed to remove Event Rule</error>');

                return MultiFlexiCommand::FAILURE;

            default:
                $output->writeln("<error>Unknown action: {$action}</error>");

                return MultiFlexiCommand::FAILURE;
        }
    }
}

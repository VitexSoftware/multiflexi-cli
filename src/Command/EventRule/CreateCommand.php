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

namespace MultiFlexi\Cli\Command\EventRule;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\EventRule;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-rule:create';

    protected function configure(): void
    {
        $this
            ->setName('event-rule:create')
            ->setDescription('Create an event rule')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('event_source_id', null, InputOption::VALUE_REQUIRED, 'Event Source ID')
            ->addOption('evidence', null, InputOption::VALUE_REQUIRED, 'Evidence pattern')
            ->addOption('operation', null, InputOption::VALUE_REQUIRED, 'Operation: any|create|update|delete', 'any')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID to trigger')
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'Priority', '0')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'Enabled (1|0)', '1')
            ->addOption('env_mapping', null, InputOption::VALUE_REQUIRED, 'JSON env variable mapping');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $sourceId = $input->getOption('event_source_id');
        $runtemplateId = $input->getOption('runtemplate_id');

        if (empty($sourceId) || empty($runtemplateId)) {
            $output->writeln('<error>Missing --event_source_id or --runtemplate_id</error>');

            return self::FAILURE;
        }

        $envMapping = $input->getOption('env_mapping');

        if (!empty($envMapping) && null === json_decode($envMapping, true)) {
            $output->writeln('<error>Invalid JSON in --env_mapping</error>');

            return self::FAILURE;
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
                $output->writeln('Event rule created with ID: '.$id);
            }

            return self::SUCCESS;
        }

        $output->writeln('<error>Failed to create event rule</error>');

        return self::FAILURE;
    }
}

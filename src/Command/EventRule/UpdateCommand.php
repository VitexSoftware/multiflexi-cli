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

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-rule:update';

    protected function configure(): void
    {
        $this
            ->setDescription('Update an event rule')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Event Rule ID')
            ->addOption('event_source_id', null, InputOption::VALUE_REQUIRED, 'Event Source ID')
            ->addOption('evidence', null, InputOption::VALUE_REQUIRED, 'Evidence pattern')
            ->addOption('operation', null, InputOption::VALUE_REQUIRED, 'Operation: any|create|update|delete')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'Priority')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'Enabled (1|0)')
            ->addOption('env_mapping', null, InputOption::VALUE_REQUIRED, 'JSON env variable mapping');
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

        foreach (['event_source_id', 'evidence', 'operation', 'runtemplate_id', 'priority', 'enabled', 'env_mapping'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        if (isset($data['env_mapping']) && null === json_decode($data['env_mapping'], true)) {
            $output->writeln('<error>Invalid JSON in --env_mapping</error>');

            return self::FAILURE;
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        (new EventRule((int) $id))->updateToSQL($data, ['id' => $id]);

        if ($format === 'json') {
            $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('Event rule updated successfully');
        }

        return self::SUCCESS;
    }
}

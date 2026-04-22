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

namespace MultiFlexi\Cli\Command\RunTemplate;

use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:get';

    protected function configure(): void
    {
        $this
            ->setName('run-template:get')
            ->setDescription('Get a run template by ID or name')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'RunTemplate name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $name = $input->getOption('name');

        if (empty($id) && empty($name)) {
            $output->writeln('<error>Missing --id or --name</error>');

            return self::FAILURE;
        }

        if (!empty($id)) {
            $runtemplate = new RunTemplate((int) $id);
        } else {
            $row = (new RunTemplate())->listingQuery()->where('name', $name)->fetch();

            if (!$row) {
                $output->writeln('<error>RunTemplate not found by name</error>');

                return self::FAILURE;
            }

            $runtemplate = new RunTemplate((int) $row['id']);
        }

        $fields = $input->getOption('fields');

        if ($fields) {
            $fieldsArray = explode(',', $fields);
            $data = array_filter($runtemplate->getData(), static fn ($key) => \in_array($key, $fieldsArray, true), \ARRAY_FILTER_USE_KEY);

            if ($format === 'json') {
                $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
            } else {
                foreach ($data as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(array_merge($runtemplate->getData(), $runtemplate->getEnvironment()->getEnvArray()), \JSON_PRETTY_PRINT));
            } else {
                foreach ($runtemplate->getData() as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }

                foreach ($runtemplate->getRuntemplateEnvironment()->getEnvArray() as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        }

        return self::SUCCESS;
    }
}

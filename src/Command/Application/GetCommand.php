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

namespace MultiFlexi\Cli\Command\Application;

use MultiFlexi\Application;
use MultiFlexi\Cli\Command\MultiFlexiCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'application:get';

    protected function configure(): void
    {
        $this
            ->setDescription('Get an application by id, uuid, or name')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Application UUID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Application name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $uuid = $input->getOption('uuid');
        $name = $input->getOption('name');

        if (!empty($id)) {
            $application = new Application((int) $id);
        } elseif (!empty($uuid)) {
            $found = (new Application())->listingQuery()->where(['uuid' => $uuid])->fetch();
            $application = $found ? new Application($found['id']) : null;
        } elseif (!empty($name)) {
            $found = (new Application())->listingQuery()->where(['name' => $name])->fetch();
            $application = $found ? new Application($found['id']) : null;
        } else {
            $output->writeln('<error>Missing --id, --uuid or --name</error>');

            return self::FAILURE;
        }

        if (empty($application) || empty($application->getData())) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'not found', 'message' => 'No application found with given identifier'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>No application found with given identifier</error>');
            }

            return self::FAILURE;
        }

        $fields = $input->getOption('fields');

        if ($fields) {
            $fieldList = explode(',', $fields);
            $data = array_filter($application->getData(), static fn ($key) => \in_array($key, $fieldList, true), \ARRAY_FILTER_USE_KEY);
        } else {
            $data = $application->getData();
        }

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

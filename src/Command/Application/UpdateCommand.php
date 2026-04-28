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

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'application:update';

    protected function configure(): void
    {
        $this
            ->setName('application:update')
            ->setDescription('Update an application')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Application UUID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Description')
            ->addOption('tags', null, InputOption::VALUE_REQUIRED, 'Tags')
            ->addOption('executable', null, InputOption::VALUE_REQUIRED, 'Executable')
            ->addOption('ociimage', null, InputOption::VALUE_OPTIONAL, 'OCI Image')
            ->addOption('requirements', null, InputOption::VALUE_OPTIONAL, 'Requirements')
            ->addOption('homepage', null, InputOption::VALUE_OPTIONAL, 'Homepage URL')
            ->addOption('appversion', null, InputOption::VALUE_OPTIONAL, 'Application Version');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $uuid = $input->getOption('uuid');

        if (empty($id) && empty($uuid)) {
            $output->writeln('<error>Missing --id or --uuid</error>');

            return self::FAILURE;
        }

        $whereCondition = [];

        if (!empty($uuid)) {
            $found = (new Application())->listingQuery()->where(['uuid' => $uuid])->fetch();

            if (!$found) {
                $output->writeln('<error>No application found with given UUID</error>');

                return self::FAILURE;
            }

            $id = $found['id'];
            $whereCondition = ['uuid' => $uuid];
        } else {
            $whereCondition = ['id' => (int) $id];
        }

        $data = [];

        foreach (['name', 'description', 'appversion', 'tags', 'executable', 'uuid', 'ociimage', 'requirements', 'homepage'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field === 'appversion' ? 'version' : $field] = $val;
            }
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        $app = new Application((int) $id);
        $app->updateToSQL($data, $whereCondition);

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = $app->getData();

            if ($format === 'json') {
                $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['updated' => true, 'application_id' => $id], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Application updated successfully (ID: {$id})");
            }
        }

        return self::SUCCESS;
    }
}

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

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'application:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create an application')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Description')
            ->addOption('tags', null, InputOption::VALUE_REQUIRED, 'Tags')
            ->addOption('executable', null, InputOption::VALUE_REQUIRED, 'Executable')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'UUID')
            ->addOption('ociimage', null, InputOption::VALUE_OPTIONAL, 'OCI Image')
            ->addOption('requirements', null, InputOption::VALUE_OPTIONAL, 'Requirements')
            ->addOption('homepage', null, InputOption::VALUE_OPTIONAL, 'Homepage URL')
            ->addOption('appversion', null, InputOption::VALUE_OPTIONAL, 'Application Version');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $data = [];

        foreach (['name', 'description', 'appversion', 'tags', 'executable', 'uuid', 'ociimage', 'requirements', 'homepage'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field === 'appversion' ? 'version' : $field] = $val;
            }
        }

        if (!isset($data['image'])) {
            $data['image'] = '';
        }

        if (empty($data['name']) || empty($data['uuid']) || empty($data['executable'])) {
            $output->writeln('<error>Missing --name, --uuid or --executable</error>');

            return self::FAILURE;
        }

        $exists = (new Application())->listingQuery()->where(['uuid' => $data['uuid']])->fetch();

        if ($exists) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'warning', 'message' => 'Application with this UUID already exists.'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<comment>Application with this UUID already exists.</comment>');
            }

            return self::FAILURE;
        }

        $app = new Application();
        $app->takeData($data);
        $appId = $app->saveToSQL();

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = (new Application((int) $appId))->getData();

            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'created', 'application' => $full], \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['application_id' => $appId], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Application created with ID: {$appId}");
            }
        }

        return self::SUCCESS;
    }
}

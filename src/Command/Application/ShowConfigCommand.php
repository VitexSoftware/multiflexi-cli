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
use MultiFlexi\Conffield;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowConfigCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'application:show-config';

    protected function configure(): void
    {
        $this
            ->setName('application:show-config')
            ->setDescription('Show configuration fields of an application')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Application UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $id = $input->getOption('id');
        $uuid = $input->getOption('uuid');

        if (empty($id) && empty($uuid)) {
            $msg = 'Missing --id or --uuid';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        if (!empty($uuid)) {
            $found = (new Application())->listingQuery()->where(['uuid' => $uuid])->fetch();

            if (!$found) {
                $msg = 'No application found with given UUID';
                $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

                return self::FAILURE;
            }

            $id = $found['id'];
        }

        $app = new Application((int) $id);

        if (empty($app->getData())) {
            $msg = "Application #{$id} not found";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $result = array_values((new Conffield())->appConfigs((int) $id));

        if ($format === 'json') {
            $output->writeln(json_encode($result, \JSON_PRETTY_PRINT));
        } elseif (empty($result)) {
            $output->writeln('<info>No configuration fields defined for this application.</info>');
        } else {
            $output->writeln(self::outputTable($result));
        }

        return self::SUCCESS;
    }
}

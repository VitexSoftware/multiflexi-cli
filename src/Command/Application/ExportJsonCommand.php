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

class ExportJsonCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'application:export-json';

    protected function configure(): void
    {
        $this
            ->setName('application:export-json')
            ->setDescription('Export an application to a JSON file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Destination JSON file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $json = $input->getOption('file');

        if (empty($id) || empty($json)) {
            $output->writeln('<error>Missing --id or --file</error>');

            return self::FAILURE;
        }

        $app = new Application((int) $id);
        file_put_contents($json, $app->getAppJson());

        if ($format === 'json') {
            $output->writeln(json_encode(['exported' => true, 'file' => $json], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Application exported to: {$json}");
        }

        return self::SUCCESS;
    }
}

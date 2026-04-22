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

class ImportJsonCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'application:import-json';

    protected function configure(): void
    {
        $this
            ->setName('application:import-json')
            ->setDescription('Import an application from a JSON file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $json = $input->getOption('file');

        if (empty($json) || !file_exists($json)) {
            $output->writeln('<error>Missing or invalid --file '.$json.'</error>');

            return self::FAILURE;
        }

        $app = new Application();
        $result = $app->importAppJson($json);

        if (!empty($result)) {
            $app->updateToSQL(['deffile' => realpath($json)], ['id' => $app->getMyKey()]);
        }

        if ($format === 'json') {
            $output->writeln(json_encode(['imported' => $result], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln($result ? '<info>'._('MultiFlexi Application imported successfully').': '.$result['name'].'</info>' : 'Failed to import application');
        }

        return self::SUCCESS;
    }
}

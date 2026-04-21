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

class RemoveJsonCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'application:remove-json';

    protected function configure(): void
    {
        $this
            ->setDescription('Remove an application defined by a JSON file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $json = $input->getOption('file');

        if (empty($json) || !file_exists($json)) {
            $output->writeln('<error>Missing or invalid --file</error>');

            return self::FAILURE;
        }

        $result = (new Application())->jsonAppRemove($json);

        if ($format === 'json') {
            $output->writeln(json_encode(['removed' => $result], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln($result ? 'Application removed successfully' : 'Failed to remove application');
        }

        return self::SUCCESS;
    }
}

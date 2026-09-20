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

namespace MultiFlexi\Cli\Command\ConfField;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends BaseCommand
{
    protected static $defaultName = 'conffield:get';

    protected function configure(): void
    {
        $this
            ->setName('conffield:get')
            ->setDescription('Get a configuration field by ID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Configuration field ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $id = (int) $input->getOption('id');
        $row = self::loadField($id);

        if ($row === null) {
            $msg = "Configuration field #{$id} not found";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        if ($format === 'json') {
            $output->writeln(json_encode($row, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(self::outputTable([$row]));
        }

        return self::SUCCESS;
    }
}

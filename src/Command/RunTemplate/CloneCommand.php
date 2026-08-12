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

class CloneCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:clone';

    protected function configure(): void
    {
        $this
            ->setName('run-template:clone')
            ->setDescription('Clone a run template; the clone is always created disabled')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Source RunTemplate ID')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Name for the clone (default: "<source name> Clone")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing --id'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing --id</error>');
            }

            return self::FAILURE;
        }

        $source = new RunTemplate((int) $id);

        if (empty($source->getMyKey())) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'RunTemplate not found'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>RunTemplate not found: '.$id.'</error>');
            }

            return self::FAILURE;
        }

        $newName = $input->getOption('name') ?: $source->getRecordName().' '._('Clone');
        $newId = $source->cloneAs($newName);

        if (!$newId) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Failed to create clone'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Failed to create clone</error>');
            }

            return self::FAILURE;
        }

        if ($format === 'json') {
            $output->writeln(json_encode(['runtemplate_id' => $newId, 'name' => $newName, 'active' => false], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("RunTemplate cloned as ID: {$newId} (disabled — review and enable when ready)");
        }

        return self::SUCCESS;
    }
}

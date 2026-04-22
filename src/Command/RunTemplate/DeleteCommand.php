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

class DeleteCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:delete';

    protected function configure(): void
    {
        $this
            ->setName('run-template:delete')
            ->setDescription('Delete a run template')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        (new RunTemplate((int) $id))->deleteFromSQL();

        if ($format === 'json') {
            $output->writeln(json_encode(['runtemplate_id' => $id, 'deleted' => true], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("RunTemplate deleted successfully (ID: {$id})");
        }

        return self::SUCCESS;
    }
}

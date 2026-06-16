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

namespace MultiFlexi\Cli\Command\Company;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Company;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'company:remove';

    protected function configure(): void
    {
        $this
            ->setName('company:remove')
            ->setDescription('Remove a company')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Company ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        (new Company((int) $id))->deleteFromSQL(['id' => $id]);

        if ($format === 'json') {
            $output->writeln(json_encode(['company_id' => $id, 'removed' => true], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Company removed: ID={$id}");
        }

        return self::SUCCESS;
    }
}

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

class ListCredentialsCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:list-credentials';

    protected function configure(): void
    {
        $this
            ->setName('run-template:list-credentials')
            ->setDescription('List credentials assigned to a run template')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('runtemplate_id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));

        $runtemplateId = $input->getOption('runtemplate_id');

        if (empty($runtemplateId) || !is_numeric($runtemplateId)) {
            $msg = 'Missing or invalid --runtemplate_id';
            $output->writeln($format === 'json' ? json_encode(['status' => 'error', 'message' => $msg]) : "<error>{$msg}</error>");

            return self::FAILURE;
        }

        $rt = new RunTemplate((int) $runtemplateId);
        $credentials = $rt->getCredentialsAssigned();

        if ($format === 'json') {
            $output->writeln(json_encode(array_values($credentials), \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(self::outputTable(array_values($credentials)));
        }

        return self::SUCCESS;
    }
}

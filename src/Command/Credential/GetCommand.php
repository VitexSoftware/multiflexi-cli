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

namespace MultiFlexi\Cli\Command\Credential;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Credential;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'credential:get';

    protected function configure(): void
    {
        $this
            ->setDescription('Get a credential by ID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $format === 'json' ? $this->jsonError($output, 'Missing --id') : $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $credential = new Credential((int) $id);

        if (empty($credential->getData())) {
            $format === 'json' ? $this->jsonError($output, 'No credential found with given ID', 'not found') : $output->writeln('<error>No credential found with given ID</error>');

            return self::FAILURE;
        }

        $fields = $input->getOption('fields');
        $data = $fields
            ? array_filter($credential->getData(), static fn ($key) => \in_array($key, explode(',', $fields), true), \ARRAY_FILTER_USE_KEY)
            : $credential->getData();

        if ($format === 'json') {
            $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
        } else {
            foreach ($data as $k => $v) {
                $output->writeln("{$k}: {$v}");
            }
        }

        return self::SUCCESS;
    }
}

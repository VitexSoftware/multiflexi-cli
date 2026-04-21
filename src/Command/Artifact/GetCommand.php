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

namespace MultiFlexi\Cli\Command\Artifact;

use MultiFlexi\Artifact;
use MultiFlexi\Cli\Command\MultiFlexiCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'artifact:get';

    protected function configure(): void
    {
        $this
            ->setDescription('Get an artifact by ID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Artifact ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $format === 'json' ? $this->jsonError($output, 'Missing --id') : $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $results = (new Artifact())->listingQuery()->where('id = ?', [(int) $id])->fetchAll();
        $data = !empty($results) ? $results[0] : null;

        if (empty($data)) {
            $format === 'json' ? $this->jsonError($output, "Artifact not found: ID={$id}") : $output->writeln("<error>Artifact not found: ID={$id}</error>");

            return self::FAILURE;
        }

        $fields = $input->getOption('fields');

        if ($fields) {
            $data = array_filter($data, static fn ($key) => \in_array($key, explode(',', $fields), true), \ARRAY_FILTER_USE_KEY);
        }

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

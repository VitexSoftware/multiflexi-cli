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

namespace MultiFlexi\Cli\Command\CredentialPrototype;

use MultiFlexi\CredentialProtoType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:list';

    protected function configure(): void
    {
        $this
            ->setName('credential-prototype:list')
            ->setDescription('List credential prototypes')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for results')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of fields to include in output')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $query = (new CredentialProtoType())->listingQuery();

        $order = $input->getOption('order');

        if (!empty($order)) {
            $query = $query->orderBy('id '.(strtoupper($order) === 'D' ? 'DESC' : 'ASC'));
        }

        $dbPrototypes = $query->fetchAll();
        $allPrototypes = array_merge($dbPrototypes, self::getFilesystemCredentialPrototypes());

        $offset = $input->getOption('offset');

        if (!empty($offset) && is_numeric($offset)) {
            $allPrototypes = \array_slice($allPrototypes, (int) $offset);
        }

        $limit = $input->getOption('limit');

        if (!empty($limit) && is_numeric($limit)) {
            $allPrototypes = \array_slice($allPrototypes, 0, (int) $limit);
        }

        $fields = $input->getOption('fields');

        if (!empty($fields)) {
            $fieldList = array_map('trim', explode(',', $fields));
            $allPrototypes = array_map(static fn ($p) => array_intersect_key($p, array_flip($fieldList)), $allPrototypes);
        }

        if ($format === 'json') {
            $output->writeln(json_encode($allPrototypes, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln($this->outputTable($allPrototypes));
        }

        return self::SUCCESS;
    }
}

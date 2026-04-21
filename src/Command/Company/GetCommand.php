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

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'company:get';

    protected function configure(): void
    {
        $this
            ->setDescription('Get a company by id, ic, name, or slug')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('ic', null, InputOption::VALUE_OPTIONAL, 'IC')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Company name')
            ->addOption('slug', null, InputOption::VALUE_REQUIRED, 'Company slug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $ic = $input->getOption('ic');
        $name = $input->getOption('name');
        $slug = $input->getOption('slug');

        if (!empty($id)) {
            $company = new Company((int) $id);
        } elseif (!empty($ic)) {
            $found = (new Company())->listingQuery()->where(['ic' => $ic])->fetch();
            $company = $found ? new Company($found['id']) : null;
        } elseif (!empty($name)) {
            $found = (new Company())->listingQuery()->where(['name' => $name])->fetch();
            $company = $found ? new Company($found['id']) : null;
        } elseif (!empty($slug)) {
            $found = (new Company())->listingQuery()->where(['slug' => $slug])->fetch();
            $company = $found ? new Company($found['id']) : null;
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing --id, --ic, --name or --slug'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing --id, --ic, --name or --slug</error>');
            }

            return self::FAILURE;
        }

        if (empty($company) || empty($company->getData())) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'not found', 'message' => 'No company found with given identifier'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>No company found with given identifier</error>');
            }

            return self::FAILURE;
        }

        $fields = $input->getOption('fields');
        $data = $fields
            ? array_filter($company->getData(), static fn ($key) => \in_array($key, explode(',', $fields), true), \ARRAY_FILTER_USE_KEY)
            : $company->getData();

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

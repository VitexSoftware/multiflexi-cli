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

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'company:create';

    protected function configure(): void
    {
        $this
            ->setName('company:create')
            ->setDescription('Create a company')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Company name')
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'Customer')
            ->addOption('enabled', null, InputOption::VALUE_OPTIONAL, 'Enabled (true/false)')
            ->addOption('settings', null, InputOption::VALUE_OPTIONAL, 'Settings')
            ->addOption('logo', null, InputOption::VALUE_OPTIONAL, 'Logo')
            ->addOption('ic', null, InputOption::VALUE_OPTIONAL, 'IC')
            ->addOption('slug', null, InputOption::VALUE_REQUIRED, 'Company Slug')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email')
            ->addOption('zabbix_host', null, InputOption::VALUE_OPTIONAL, 'Zabbix Host');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $data = [];

        foreach (['name', 'customer', 'enabled', 'settings', 'logo', 'ic', 'slug', 'email'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $field === 'enabled' ? $this->parseBoolOption($val) : $val;
            }
        }

        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = strtolower(preg_replace('/[^a-z0-9]+/', '_', $data['name']));
        }

        if (empty($data['name'])) {
            $output->writeln('<error>Missing --name</error>');

            return self::FAILURE;
        }

        $exists = (new Company())->listingQuery()->where(['name' => $data['name']])->fetch();

        if ($exists) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Company with this name already exists'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Company with this name already exists</error>');
            }

            return self::FAILURE;
        }

        $company = new Company();
        $company->takeData($data);
        $companyId = $company->saveToSQL();

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = (new Company((int) $companyId))->getData();

            if ($format === 'json') {
                $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['company_id' => $companyId], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Company created with ID: {$companyId}");
            }
        }

        return self::SUCCESS;
    }
}

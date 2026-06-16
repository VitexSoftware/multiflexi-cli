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

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'company:update';

    protected function configure(): void
    {
        $this
            ->setName('company:update')
            ->setDescription('Update a company')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Company name')
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'Customer')
            ->addOption('enabled', null, InputOption::VALUE_OPTIONAL, 'Enabled (true/false)')
            ->addOption('settings', null, InputOption::VALUE_OPTIONAL, 'Settings')
            ->addOption('logo', null, InputOption::VALUE_OPTIONAL, 'Logo')
            ->addOption('ic', null, InputOption::VALUE_OPTIONAL, 'IC')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email')
            ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'Company Slug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $data = [];

        foreach (['name', 'customer', 'enabled', 'settings', 'logo', 'ic', 'email', 'slug'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $field === 'enabled' ? $this->parseBoolOption($val) : $val;
            }
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        $company = new Company((int) $id);
        $current = $company->getData();
        $changed = false;

        foreach ($data as $k => $v) {
            if (!\array_key_exists($k, $current) || $current[$k] !== $v) {
                $changed = true;

                break;
            }
        }

        if (!$changed) {
            if ($format === 'json') {
                $output->writeln(json_encode(['updated' => false, 'company_id' => $id, 'message' => 'No changes detected'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("No changes detected for company ID: {$id}");
            }

            return self::SUCCESS;
        }

        $company->updateToSQL($data, ['id' => $id]);

        if ($format === 'json') {
            $output->writeln(json_encode(['updated' => true, 'company_id' => $id], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Company updated: ID={$id}");
        }

        return self::SUCCESS;
    }
}

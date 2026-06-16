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
use MultiFlexi\Company;
use MultiFlexi\Credential;
use MultiFlexi\CredentialType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'credential:create';

    protected function configure(): void
    {
        $this
            ->setName('credential:create')
            ->setDescription('Create a credential')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Credential name')
            ->addOption('company-id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('credential-type-id', null, InputOption::VALUE_REQUIRED, 'Credential Type ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $name = $input->getOption('name');
        $companyId = $input->getOption('company-id');
        $credentialTypeId = $input->getOption('credential-type-id');

        if (empty($credentialTypeId)) {
            $format === 'json' ? $this->jsonError($output, 'Missing --credential-type-id') : $output->writeln('<error>Missing --credential-type-id</error>');

            return self::FAILURE;
        }

        if (empty($companyId)) {
            $format === 'json' ? $this->jsonError($output, 'Missing --company-id') : $output->writeln('<error>Missing --company-id</error>');

            return self::FAILURE;
        }

        if (empty((new Company((int) $companyId))->getData())) {
            $format === 'json' ? $this->jsonError($output, 'Company with given ID not found') : $output->writeln('<error>Company with given ID not found</error>');

            return self::FAILURE;
        }

        if (empty((new CredentialType((int) $credentialTypeId))->getData())) {
            $format === 'json' ? $this->jsonError($output, 'Credential type with given ID not found') : $output->writeln('<error>Credential type with given ID not found</error>');

            return self::FAILURE;
        }

        $data = ['company_id' => (int) $companyId, 'credential_type_id' => (int) $credentialTypeId];

        if (!empty($name)) {
            $data['name'] = $name;
        }

        try {
            $credential = new Credential();
            $credentialId = $credential->insertToSQL($data);
            $format === 'json' ? $this->jsonSuccess($output, 'Credential created successfully', ['credential_id' => $credentialId, 'created' => true]) : $output->writeln("<info>Credential created successfully with ID: {$credentialId}</info>");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $format === 'json' ? $this->jsonError($output, 'Failed to create credential: '.$e->getMessage()) : $output->writeln('<error>Failed to create credential: '.$e->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}

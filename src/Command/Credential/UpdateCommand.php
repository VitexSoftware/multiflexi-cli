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

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'credential:update';

    protected function configure(): void
    {
        $this
            ->setName('credential:update')
            ->setDescription('Update a credential')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Credential name')
            ->addOption('company-id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('credential-type-id', null, InputOption::VALUE_REQUIRED, 'Credential Type ID');
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

        $data = [];
        $name = $input->getOption('name');

        if (!empty($name)) {
            $data['name'] = $name;
        }

        $companyId = $input->getOption('company-id');

        if (!empty($companyId)) {
            if (empty((new Company((int) $companyId))->getData())) {
                $format === 'json' ? $this->jsonError($output, 'Company with given ID not found') : $output->writeln('<error>Company with given ID not found</error>');

                return self::FAILURE;
            }

            $data['company_id'] = (int) $companyId;
        }

        $credentialTypeId = $input->getOption('credential-type-id');

        if (!empty($credentialTypeId)) {
            if (empty((new CredentialType((int) $credentialTypeId))->getData())) {
                $format === 'json' ? $this->jsonError($output, 'Credential type with given ID not found') : $output->writeln('<error>Credential type with given ID not found</error>');

                return self::FAILURE;
            }

            $data['credential_type_id'] = (int) $credentialTypeId;
        }

        if (empty($data)) {
            $format === 'json' ? $this->jsonError($output, 'No fields to update') : $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        try {
            $credential->updateToSQL($data, ['id' => $id]);
            $format === 'json' ? $this->jsonSuccess($output, 'Credential updated successfully', ['credential_id' => (int) $id, 'updated' => true]) : $output->writeln('<info>Credential updated successfully</info>');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $format === 'json' ? $this->jsonError($output, 'Failed to update credential: '.$e->getMessage()) : $output->writeln('<error>Failed to update credential: '.$e->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}

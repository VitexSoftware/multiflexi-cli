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

namespace MultiFlexi\Cli\Command\CredentialType;

use MultiFlexi\CredentialProtoType;
use MultiFlexi\CredentialType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends BaseCommand
{
    protected static $defaultName = 'credential-type:create';

    protected function configure(): void
    {
        $this
            ->setName('credential-type:create')
            ->setDescription('Create a credential type for a company')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('company-id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('class', null, InputOption::VALUE_REQUIRED, 'Class name (credential prototype code)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $companyId = $input->getOption('company-id');
        $className = $input->getOption('class');

        if (empty($companyId) || empty($className)) {
            $output->writeln('<error>Missing --company-id or --class</error>');

            return self::FAILURE;
        }

        $proto = (new CredentialProtoType())->listingQuery()->where(['code' => $className])->fetch();

        if (!$proto) {
            $output->writeln('<error>No credential prototype found for class: '.$className.'</error>');

            return self::FAILURE;
        }

        $credType = new CredentialType();
        $data = ['company_id' => (int) $companyId, 'class' => $className, 'name' => $proto['name'] ?? $className, 'uuid' => $proto['uuid'] ?? null, 'version' => $proto['version'] ?? '1.0'];
        $result = $credType->insertToSQL($data);

        if ($result) {
            if ($format === 'json') {
                $output->writeln(json_encode($credType->getData(), \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('Credential type created successfully. ID: '.$credType->getMyKey());
            }

            return self::SUCCESS;
        }

        if ($format === 'json') {
            $output->writeln(json_encode(['error' => 'Failed to create credential type'], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('<error>Failed to create credential type</error>');
        }

        return self::FAILURE;
    }
}

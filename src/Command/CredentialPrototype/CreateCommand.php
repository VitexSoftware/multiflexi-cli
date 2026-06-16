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

class CreateCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:create';

    protected function configure(): void
    {
        $this
            ->setName('credential-prototype:create')
            ->setDescription('Create a credential prototype')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Code')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'UUID')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Description')
            ->addOption('prototype-version', null, InputOption::VALUE_REQUIRED, 'Version')
            ->addOption('logo', null, InputOption::VALUE_REQUIRED, 'Logo URL')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Homepage URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $data = [];

        foreach (['code', 'name', 'uuid'] as $field) {
            $val = $input->getOption($field);

            if ($val === null) {
                $output->writeln("<error>Missing required field: --{$field}</error>");

                return self::FAILURE;
            }

            $data[$field] = $val;
        }

        foreach (['description', 'prototype-version', 'logo', 'url'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field === 'prototype-version' ? 'version' : $field] = $val;
            }
        }

        if (!isset($data['version'])) {
            $data['version'] = '1.0';
        }

        $credProto = new CredentialProtoType();

        if (!$credProto->validateCodeFormat($data['code'])) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Code validation failed'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Code validation failed</error>');
            }

            return self::FAILURE;
        }

        $credProto->setData($data);
        $result = $credProto->saveToSQL();

        if ($result) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'success', 'message' => 'Credential prototype created successfully', 'id' => $credProto->getMyKey(), 'code' => $data['code'], 'uuid' => $data['uuid']], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<info>Credential prototype created successfully</info>');
                $output->writeln('<info>ID: '.$credProto->getMyKey().'</info>');
            }

            return self::SUCCESS;
        }

        if ($format === 'json') {
            $output->writeln(json_encode(['status' => 'error', 'message' => 'Failed to create credential prototype'], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('<error>Failed to create credential prototype</error>');
        }

        return self::FAILURE;
    }
}

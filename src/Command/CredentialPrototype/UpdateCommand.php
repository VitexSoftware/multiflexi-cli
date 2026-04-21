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

class UpdateCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:update';

    protected function configure(): void
    {
        $this
            ->setDescription('Update a credential prototype')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential Prototype ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'UUID')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Code')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Description')
            ->addOption('prototype-version', null, InputOption::VALUE_REQUIRED, 'Version')
            ->addOption('logo', null, InputOption::VALUE_REQUIRED, 'Logo URL')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Homepage URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $uuid = $input->getOption('uuid');
        $code = $input->getOption('code');

        if (empty($id) && empty($uuid) && empty($code)) {
            $output->writeln('<error>Missing --id, --uuid, or --code</error>');

            return self::FAILURE;
        }

        $credProto = new CredentialProtoType();

        if (!empty($uuid)) {
            $found = $credProto->listingQuery()->where(['uuid' => $uuid])->fetch();

            if (!$found) {
                $output->writeln('<error>No credential prototype found with given UUID</error>');

                return self::FAILURE;
            }

            $id = $found['id'];
        } elseif (!empty($code)) {
            $found = $credProto->listingQuery()->where(['code' => $code])->fetch();

            if (!$found) {
                $output->writeln('<error>No credential prototype found with given code</error>');

                return self::FAILURE;
            }

            $id = $found['id'];
        }

        $data = [];

        foreach (['name', 'description', 'prototype-version', 'logo', 'url'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field === 'prototype-version' ? 'version' : $field] = $val;
            }
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        (new CredentialProtoType((int) $id))->updateToSQL($data, ['id' => $id]);

        if ($format === 'json') {
            $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('Credential prototype updated successfully');
        }

        return self::SUCCESS;
    }
}

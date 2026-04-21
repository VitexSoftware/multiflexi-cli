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

class GetCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:get';

    protected function configure(): void
    {
        $this
            ->setDescription('Get a credential prototype by id, uuid, or code')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential Prototype ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Credential Prototype UUID')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Credential Prototype Code');
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

        $credProto = new CredentialProtoType((int) $id);
        $data = $credProto->getData();
        $fields = (new \MultiFlexi\CredentialProtoTypeField())->listingQuery()->where(['credential_prototype_id' => $id])->fetchAll();

        if ($format === 'json') {
            $data['fields'] = $fields;
            $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
        } else {
            foreach ($data as $k => $v) {
                $output->writeln("{$k}: {$v}");
            }

            if (!empty($fields)) {
                $output->writeln("\nFields:");

                foreach ($fields as $fieldData) {
                    $output->writeln("  - {$fieldData['name']} ({$fieldData['type']})");
                }
            }
        }

        return self::SUCCESS;
    }
}

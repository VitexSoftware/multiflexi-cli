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

class ExportJsonCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:export-json';

    protected function configure(): void
    {
        $this
            ->setDescription('Export a credential prototype to a JSON file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential Prototype ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'UUID')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Code')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Destination JSON file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $uuid = $input->getOption('uuid');
        $code = $input->getOption('code');
        $file = $input->getOption('file');

        if (empty($id) && empty($uuid) && empty($code)) {
            $output->writeln('<error>Missing --id, --uuid, or --code</error>');

            return self::FAILURE;
        }

        if (empty($file)) {
            $output->writeln('<error>Missing --file</error>');

            return self::FAILURE;
        }

        $credProto = new CredentialProtoType();

        if (!empty($uuid)) {
            $found = $credProto->listingQuery()->where(['uuid' => $uuid])->fetch();
            $id = $found ? $found['id'] : null;
        } elseif (!empty($code)) {
            $found = $credProto->listingQuery()->where(['code' => $code])->fetch();
            $id = $found ? $found['id'] : null;
        }

        if (empty($id)) {
            $output->writeln('<error>No credential prototype found</error>');

            return self::FAILURE;
        }

        $credProto = new CredentialProtoType((int) $id);
        $data = $credProto->getData();
        $exportData = ['uuid' => $data['uuid'], 'code' => $data['code'], 'name' => $data['name'], 'description' => $data['description'] ?? '', 'version' => $data['version'] ?? '1.0', 'logo' => $data['logo'] ?? null, 'url' => $data['url'] ?? null, 'fields' => []];
        $result = file_put_contents($file, json_encode($exportData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

        if ($result !== false) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'success', 'message' => 'Exported successfully', 'file' => $file], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<info>Credential prototype exported to: '.$file.'</info>');
            }

            return self::SUCCESS;
        }

        if ($format === 'json') {
            $output->writeln(json_encode(['status' => 'error', 'message' => 'Failed to export', 'file' => $file], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('<error>Failed to export credential prototype to: '.$file.'</error>');
        }

        return self::FAILURE;
    }
}

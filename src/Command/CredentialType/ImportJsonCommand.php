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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportJsonCommand extends BaseCommand
{
    protected static $defaultName = 'credential-type:import-json';

    protected function configure(): void
    {
        $this
            ->setDescription('Import a credential type from a JSON file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $file = $input->getOption('file');

        if (empty($file) || !file_exists($file)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing or invalid --file'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing or invalid --file</error>');
            }

            return self::FAILURE;
        }

        $violations = $this->validateCredTypeJson($file);

        if (!empty($violations)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'JSON validation failed', 'violations' => $violations, 'schema' => realpath(\MultiFlexi\CredentialType::$credTypeSchema)], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>JSON validation failed</error>');

                foreach ($violations as $v) {
                    $output->writeln('<error> '.$v.' </error>');
                }
            }

            return self::FAILURE;
        }

        try {
            $credProto = new CredentialProtoType();
            $result = $credProto->importJson($file);

            if ($result) {
                if ($format === 'json') {
                    $output->writeln(json_encode(['status' => 'success', 'file' => $file, 'credential_type_id' => $credProto->getMyKey(), 'uuid' => $credProto->getDataValue('uuid'), 'code' => $credProto->getDataValue('code'), 'imported' => true], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<info>Credential type imported successfully</info>');
                    $output->writeln('ID: '.$credProto->getMyKey());
                }

                return self::SUCCESS;
            }

            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Failed to import credential type', 'imported' => false], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Failed to import credential type</error>');
            }

            return self::FAILURE;
        } catch (\Exception $e) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => $e->getMessage()], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Import failed: '.$e->getMessage().'</error>');
            }

            return self::FAILURE;
        }
    }
}

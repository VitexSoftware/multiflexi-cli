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

class ImportJsonCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:import-json';

    protected function configure(): void
    {
        $this
            ->setName('credential-prototype:import-json')
            ->setDescription('Import a credential prototype from a JSON file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $jsonFile = $input->getOption('file');

        if (empty($jsonFile) || !file_exists($jsonFile) || !is_file($jsonFile)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing or invalid --file', 'file' => $jsonFile], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing or invalid --file: '.$jsonFile.'</error>');
            }

            return self::FAILURE;
        }

        $output->writeln('Importing '.$jsonFile.' '.filesize($jsonFile).'b');

        $rawContent = file_get_contents($jsonFile);
        $decoded = $rawContent !== false ? json_decode($rawContent, true) : null;

        if (!\is_array($decoded)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Invalid JSON content', 'file' => $jsonFile], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Invalid JSON content</error>');
            }

            return self::FAILURE;
        }

        $normalized = self::normalizePrototypeJson($decoded, 'en', 'cs');
        $normalizedPath = sys_get_temp_dir().'/crprototype.normalized.'.md5($jsonFile).'.json';
        $normalizedJson = json_encode($normalized, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);

        if ($normalizedJson === false || file_put_contents($normalizedPath, $normalizedJson) === false) {
            $output->writeln('<error>Failed to write normalized JSON to temporary file</error>');

            return self::FAILURE;
        }

        $validationResult = $this->validateCredPrototypeJson($normalizedPath);

        if (!empty($validationResult)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'JSON validation failed', 'violations' => $validationResult, 'file' => $jsonFile], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>JSON validation failed</error>');

                foreach ($validationResult as $violation) {
                    $output->writeln('<error> '.$violation.' </error>');
                }
            }

            @unlink($normalizedPath);

            return self::FAILURE;
        }

        try {
            $credProto = new CredentialProtoType();
            $uuid = $normalized['uuid'] ?? null;
            $existing = $uuid ? $credProto->listingQuery()->where(['uuid' => $uuid])->fetch() : null;

            if ($existing && isset($existing['id'])) {
                $credProto = new CredentialProtoType((int) $existing['id']);
                $result = $credProto->importJson($normalized);
                $actionType = 'updated';
            } else {
                $credProto = new CredentialProtoType();
                $result = $credProto->importJson($normalized);
                $actionType = 'imported';
            }

            @unlink($normalizedPath);

            if ($result) {
                if ($format === 'json') {
                    $output->writeln(json_encode(['status' => 'success', 'message' => 'Credential prototype '.$actionType.' successfully', 'file' => $jsonFile, 'credential_prototype_id' => $credProto->getMyKey(), $actionType => true], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<info>Credential prototype '.$actionType.' successfully</info>');
                }

                return self::SUCCESS;
            }

            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Failed to import credential prototype', 'file' => $jsonFile], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Failed to import credential prototype</error>');
            }

            return self::FAILURE;
        } catch (\Exception $e) {
            @unlink($normalizedPath);

            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Import failed: '.$e->getMessage(), 'file' => $jsonFile], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Import failed: '.$e->getMessage().'</error>');
            }

            return self::FAILURE;
        }
    }
}

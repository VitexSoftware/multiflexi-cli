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

namespace MultiFlexi\Cli\Command;

use MultiFlexi\CredentialType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of CredentialType.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class CredentialTypeCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'credtype';

    /**
     * @return array<string> errors
     */
    public function validateCredTypeJson(string $jsonFile): array
    {
        return self::validateJson($jsonFile, CredentialType::$credTypeSchema);
    }
    protected function configure(): void
    {
        $this
            ->setName('credtype')
            ->setDescription('Credential type operations')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|update|import|import-json|export-json|remove-json|validate-json')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential Type ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Credential Type UUID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file for import/export/remove/validate')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)')
            ->setHelp('This command manages Credential Types');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'list':
                $credType = new CredentialType();
                $query = $credType->listingQuery();
                
                // Handle order option
                $order = $input->getOption('order');
                if (!empty($order)) {
                    $orderBy = strtoupper($order) === 'D' ? 'DESC' : 'ASC';
                    $query = $query->orderBy('id ' . $orderBy);
                }
                
                // Handle limit option
                $limit = $input->getOption('limit');
                if (!empty($limit) && is_numeric($limit)) {
                    $query = $query->limit((int) $limit);
                }
                
                // Handle offset option
                $offset = $input->getOption('offset');
                if (!empty($offset) && is_numeric($offset)) {
                    $query = $query->offset((int) $offset);
                }
                
                $types = $query->fetchAll();
                
                // Handle fields option
                $fields = $input->getOption('fields');
                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $types = array_map(function($type) use ($fieldList) {
                        return array_intersect_key($type, array_flip($fieldList));
                    }, $types);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($types, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln($this->outputTable($types));
                }

                return MultiFlexiCommand::SUCCESS;
            case 'get':
                $id = $input->getOption('id');
                $uuid = $input->getOption('uuid');

                if (empty($id) && empty($uuid)) {
                    $output->writeln('<error>Missing --id or --uuid for credtype get</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                if (!empty($uuid)) {
                    $credType = new CredentialType();
                    $found = $credType->listingQuery()->where(['uuid' => $uuid])->fetch();

                    if (!$found) {
                        $output->writeln('<error>No credential type found with given UUID</error>');

                        return MultiFlexiCommand::FAILURE;
                    }

                    $id = $found['id'];
                }

                $credType = new CredentialType((int) $id);
                $data = $credType->getData();

                if ($format === 'json') {
                    $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
                } else {
                    foreach ($data as $k => $v) {
                        $output->writeln("{$k}: {$v}");
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'update':
                $id = $input->getOption('id');
                $uuid = $input->getOption('uuid');

                if (empty($id) && empty($uuid)) {
                    $output->writeln('<error>Missing --id or --uuid for credtype update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                if (!empty($uuid)) {
                    $credType = new CredentialType();
                    $found = $credType->listingQuery()->where(['uuid' => $uuid])->fetch();

                    if (!$found) {
                        $output->writeln('<error>No credential type found with given UUID</error>');

                        return MultiFlexiCommand::FAILURE;
                    }

                    $id = $found['id'];
                }

                $data = [];

                foreach (['name'] as $field) {
                    $val = $input->getOption($field);

                    if ($val !== null) {
                        $data[$field] = $val;
                    }
                }

                if (empty($data)) {
                    $output->writeln('<error>No fields to update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $credType = new CredentialType((int) $id);
                $credType->updateToSQL($data, ['id' => $id]);

                if ($format === 'json') {
                    $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('Credential type updated successfully');
                }

                return MultiFlexiCommand::SUCCESS;
            case 'import':
                $file = $input->getOption('file');

                if (empty($file) || !file_exists($file)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Missing or invalid --file for import',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Missing or invalid --file for import</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                // Validate JSON first
                $validationResult = $this->validateCredTypeJson($file);

                if (!empty($validationResult)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'JSON validation failed',
                            'violations' => $validationResult,
                            'file' => $file,
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>JSON validation failed</error>');

                        foreach ($validationResult as $violation) {
                            $output->writeln('<error> '.$violation.' </error>');
                        }
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                try {
                    $credType = new CredentialType();
                    $result = $credType->importCredTypeJson($file);

                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'success',
                            'message' => 'Credential type imported successfully',
                            'file' => $file,
                            'imported' => true,
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<info>Credential type imported successfully</info>');
                    }

                    return MultiFlexiCommand::SUCCESS;
                } catch (\Exception $e) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Import failed: '.$e->getMessage(),
                            'file' => $file,
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Import failed: '.$e->getMessage().'</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

            case 'import-json':
                $json = $input->getOption('file');

                if (empty($json) || !file_exists($json)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Missing or invalid --file for import-json',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Missing or invalid --file for import-json</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                // Validate JSON first
                $validationResult = $this->validateCredTypeJson($json);

                if (!empty($validationResult)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'JSON validation failed',
                            'violations' => $validationResult,
                            'file' => $json,
                            'schema' => realpath(CredentialType::$credTypeSchema),
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>JSON validation failed</error>');
                        $output->writeln('<comment>Schema: ' . realpath(CredentialType::$credTypeSchema) . '</comment>');

                        foreach ($validationResult as $violation) {
                            $output->writeln('<error> ' . $violation . ' </error>');
                        }
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                try {
                    $credType = new CredentialType();
                    $result = $credType->importCredTypeJson($json);

                    if ($result) {
                        if ($format === 'json') {
                            $output->writeln(json_encode([
                                'status' => 'success',
                                'message' => 'Credential type imported successfully',
                                'file' => $json,
                                'credential_type_id' => $credType->getMyKey(),
                                'uuid' => $credType->getDataValue('uuid'),
                                'imported' => true,
                            ], \JSON_PRETTY_PRINT));
                        } else {
                            $output->writeln('<info>Credential type imported successfully</info>');
                            $output->writeln('<info>ID: ' . $credType->getMyKey() . '</info>');
                            $output->writeln('<info>UUID: ' . $credType->getDataValue('uuid') . '</info>');
                        }

                        return MultiFlexiCommand::SUCCESS;
                    } else {
                        if ($format === 'json') {
                            $output->writeln(json_encode([
                                'status' => 'error',
                                'message' => 'Failed to import credential type',
                                'file' => $json,
                                'imported' => false,
                            ], \JSON_PRETTY_PRINT));
                        } else {
                            $output->writeln('<error>Failed to import credential type</error>');
                        }

                        return MultiFlexiCommand::FAILURE;
                    }
                } catch (\Exception $e) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Import failed: ' . $e->getMessage(),
                            'file' => $json,
                            'imported' => false,
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Import failed: ' . $e->getMessage() . '</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

            case 'validate-json':
                $json = $input->getOption('file');

                if (empty($json) || !file_exists($json)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Missing or invalid --file for validate-json',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Missing or invalid --file for validate-json</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $result = $this->validateCredTypeJson($json);

                if ($format === 'json') {
                    if (empty($result)) {
                        $output->writeln(json_encode([
                            'status' => 'success',
                            'file' => $json,
                            'schema' => realpath(CredentialType::$credTypeSchema),
                            'message' => 'JSON is valid',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'file' => $json,
                            'violations' => $result,
                            'schema' => realpath(CredentialType::$credTypeSchema),
                            'message' => 'JSON validation failed',
                        ], \JSON_PRETTY_PRINT));
                    }
                } else {
                    if (empty($result)) {
                        $output->writeln('<info>JSON is valid</info>');
                    } else {
                        $output->writeln('<error>JSON validation failed</error>');

                        foreach ($result as $violation) {
                            $output->writeln('<error> '.$violation.' </error>');
                        }
                    }
                }

                return empty($result) ? MultiFlexiCommand::SUCCESS : MultiFlexiCommand::FAILURE;

            default:
                $output->writeln("<error>Unknown action: {$action}</error>");

                return MultiFlexiCommand::FAILURE;
        }
    }
}

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

use MultiFlexi\CredentialProtoType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of CredentialProtoType command.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class CredentialProtoTypeCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'crprototype';

    /**
     * @return array<string> errors
     */
    public function validateCredPrototypeJson(string $jsonFile): array
    {
        return self::validateJson($jsonFile, \MultiFlexi\CredentialProtoType::$credTypeSchema);
    }

    protected function configure(): void
    {
        $this
            ->setName('crprototype')
            ->setDescription('Credential prototype operations')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|create|update|delete|import-json|export-json|validate-json')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential Prototype ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Credential Prototype UUID')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Credential Prototype Code')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Description')
            ->addOption('prototype-version', null, InputOption::VALUE_REQUIRED, 'Version')
            ->addOption('logo', null, InputOption::VALUE_REQUIRED, 'Logo URL')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Homepage URL')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file for import/export/validate')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)')
            ->setHelp('This command manages Credential Prototypes (JSON-based credential type definitions)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'list':
                $credProto = new \MultiFlexi\CredentialProtoType();
                $query = $credProto->listingQuery();

                // Handle order option for database results
                $order = $input->getOption('order');
                if (!empty($order)) {
                    $orderBy = strtoupper($order) === 'D' ? 'DESC' : 'ASC';
                    $query = $query->orderBy('id '.$orderBy);
                }

                // Don't apply limit/offset to database query since we'll merge with filesystem results
                $dbPrototypes = $query->fetchAll();

                // Add filesystem-based credential prototypes
                $filesystemPrototypes = $this->getFilesystemCredentialPrototypes();
                $allPrototypes = array_merge($dbPrototypes, $filesystemPrototypes);

                // Apply limit and offset after merging both sources
                $limit = $input->getOption('limit');
                $offset = $input->getOption('offset');
                
                if (!empty($offset) && is_numeric($offset)) {
                    $allPrototypes = array_slice($allPrototypes, (int) $offset);
                }
                
                if (!empty($limit) && is_numeric($limit)) {
                    $allPrototypes = array_slice($allPrototypes, 0, (int) $limit);
                }

                // Handle fields option
                $fields = $input->getOption('fields');
                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $allPrototypes = array_map(static function ($prototype) use ($fieldList) {
                        return array_intersect_key($prototype, array_flip($fieldList));
                    }, $allPrototypes);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($allPrototypes, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln($this->outputTable($allPrototypes));
                }

                return MultiFlexiCommand::SUCCESS;

            case 'get':
                $id = $input->getOption('id');
                $uuid = $input->getOption('uuid');
                $code = $input->getOption('code');

                if (empty($id) && empty($uuid) && empty($code)) {
                    $output->writeln('<error>Missing --id, --uuid, or --code for crprototype get</error>');
                    return MultiFlexiCommand::FAILURE;
                }

                $credProto = new \MultiFlexi\CredentialProtoType();

                // Find by UUID or code first
                if (!empty($uuid)) {
                    $found = $credProto->listingQuery()->where(['uuid' => $uuid])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given UUID</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                } elseif (!empty($code)) {
                    $found = $credProto->listingQuery()->where(['code' => $code])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given code</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                }

                $credProto = new \MultiFlexi\CredentialProtoType((int) $id);
                $data = $credProto->getData();

                if ($format === 'json') {
                    $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
                } else {
                    foreach ($data as $k => $v) {
                        $output->writeln("{$k}: {$v}");
                    }
                }

                return MultiFlexiCommand::SUCCESS;

            case 'create':
                $data = [];
                $requiredFields = ['code', 'name', 'uuid'];

                foreach ($requiredFields as $field) {
                    $val = $input->getOption($field);
                    if ($val === null) {
                        $output->writeln("<error>Missing required field: --{$field}</error>");
                        return MultiFlexiCommand::FAILURE;
                    }
                    $data[$field] = $val;
                }

                // Optional fields
                $optionalFields = ['description', 'prototype-version', 'logo', 'url'];
                foreach ($optionalFields as $field) {
                    $val = $input->getOption($field);
                    if ($val !== null) {
                        if ($field === 'prototype-version') {
                            $data['version'] = $val;
                        } else {
                            $data[$field] = $val;
                        }
                    }
                }

                // Set default version if not provided
                if (!isset($data['version'])) {
                    $data['version'] = '1.0';
                }

                $credProto = new \MultiFlexi\CredentialProtoType();
                
                // Validate code format
                $codeValidation = $credProto->validateCodeFormat($data['code']);
                if (!$codeValidation['valid']) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Code validation failed',
                            'errors' => $codeValidation['errors'],
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Code validation failed:</error>');
                        foreach ($codeValidation['errors'] as $error) {
                            $output->writeln('<error> '.$error.'</error>');
                        }
                    }
                    return MultiFlexiCommand::FAILURE;
                }

                $credProto->setData($data);
                $result = $credProto->saveToSQL();

                if ($result) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'success',
                            'message' => 'Credential prototype created successfully',
                            'id' => $credProto->getMyKey(),
                            'code' => $data['code'],
                            'uuid' => $data['uuid'],
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<info>Credential prototype created successfully</info>');
                        $output->writeln('<info>ID: '.$credProto->getMyKey().'</info>');
                        $output->writeln('<info>Code: '.$data['code'].'</info>');
                        $output->writeln('<info>UUID: '.$data['uuid'].'</info>');
                    }
                    return MultiFlexiCommand::SUCCESS;
                }

                if ($format === 'json') {
                    $output->writeln(json_encode([
                        'status' => 'error',
                        'message' => 'Failed to create credential prototype',
                    ], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>Failed to create credential prototype</error>');
                }
                return MultiFlexiCommand::FAILURE;

            case 'update':
                $id = $input->getOption('id');
                $uuid = $input->getOption('uuid');
                $code = $input->getOption('code');

                if (empty($id) && empty($uuid) && empty($code)) {
                    $output->writeln('<error>Missing --id, --uuid, or --code for crprototype update</error>');
                    return MultiFlexiCommand::FAILURE;
                }

                $credProto = new \MultiFlexi\CredentialProtoType();

                // Find by UUID or code first
                if (!empty($uuid)) {
                    $found = $credProto->listingQuery()->where(['uuid' => $uuid])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given UUID</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                } elseif (!empty($code)) {
                    $found = $credProto->listingQuery()->where(['code' => $code])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given code</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                }

                $data = [];
                $updateFields = ['name', 'description', 'prototype-version', 'logo', 'url'];
                foreach ($updateFields as $field) {
                    $val = $input->getOption($field);
                    if ($val !== null) {
                        if ($field === 'prototype-version') {
                            $data['version'] = $val;
                        } else {
                            $data[$field] = $val;
                        }
                    }
                }

                if (empty($data)) {
                    $output->writeln('<error>No fields to update</error>');
                    return MultiFlexiCommand::FAILURE;
                }

                $credProto = new \MultiFlexi\CredentialProtoType((int) $id);
                $credProto->updateToSQL($data, ['id' => $id]);

                if ($format === 'json') {
                    $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('Credential prototype updated successfully');
                }

                return MultiFlexiCommand::SUCCESS;

            case 'delete':
                $id = $input->getOption('id');
                $uuid = $input->getOption('uuid');
                $code = $input->getOption('code');

                if (empty($id) && empty($uuid) && empty($code)) {
                    $output->writeln('<error>Missing --id, --uuid, or --code for crprototype delete</error>');
                    return MultiFlexiCommand::FAILURE;
                }

                $credProto = new \MultiFlexi\CredentialProtoType();

                // Find by UUID or code first
                if (!empty($uuid)) {
                    $found = $credProto->listingQuery()->where(['uuid' => $uuid])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given UUID</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                } elseif (!empty($code)) {
                    $found = $credProto->listingQuery()->where(['code' => $code])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given code</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                }

                $credProto = new \MultiFlexi\CredentialProtoType((int) $id);
                $result = $credProto->deleteFromSQL();

                if ($result) {
                    if ($format === 'json') {
                        $output->writeln(json_encode(['deleted' => true], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('Credential prototype deleted successfully');
                    }
                    return MultiFlexiCommand::SUCCESS;
                }

                if ($format === 'json') {
                    $output->writeln(json_encode([
                        'status' => 'error',
                        'message' => 'Failed to delete credential prototype',
                    ], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>Failed to delete credential prototype</error>');
                }
                return MultiFlexiCommand::FAILURE;

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
                $validationResult = $this->validateCredPrototypeJson($json);
                if (!empty($validationResult)) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'JSON validation failed',
                            'violations' => $validationResult,
                            'file' => $json,
                            'schema' => realpath(\MultiFlexi\CredentialProtoType::$credTypeSchema),
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>JSON validation failed</error>');
                        $output->writeln('<comment>Schema: '.realpath(\MultiFlexi\CredentialProtoType::$credTypeSchema).'</comment>');
                        foreach ($validationResult as $violation) {
                            $output->writeln('<error> '.$violation.' </error>');
                        }
                    }
                    return MultiFlexiCommand::FAILURE;
                }

                try {
                    $credProto = new \MultiFlexi\CredentialProtoType();
                    $result = $credProto->importJson($json);

                    if ($result) {
                        if ($format === 'json') {
                            $output->writeln(json_encode([
                                'status' => 'success',
                                'message' => 'Credential prototype imported successfully',
                                'file' => $json,
                                'credential_prototype_id' => $credProto->getMyKey(),
                                'uuid' => $credProto->getDataValue('uuid'),
                                'code' => $credProto->getDataValue('code'),
                                'imported' => true,
                            ], \JSON_PRETTY_PRINT));
                        } else {
                            $output->writeln('<info>Credential prototype imported successfully</info>');
                            $output->writeln('<info>ID: '.$credProto->getMyKey().'</info>');
                            $output->writeln('<info>UUID: '.$credProto->getDataValue('uuid').'</info>');
                            $output->writeln('<info>Code: '.$credProto->getDataValue('code').'</info>');
                        }
                        return MultiFlexiCommand::SUCCESS;
                    }

                    // Handle import failure
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Failed to import credential prototype',
                            'file' => $json,
                            'imported' => false,
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Failed to import credential prototype</error>');
                    }
                    return MultiFlexiCommand::FAILURE;

                } catch (\Exception $e) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Import failed: '.$e->getMessage(),
                            'file' => $json,
                            'imported' => false,
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Import failed: '.$e->getMessage().'</error>');
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

                $result = $this->validateCredPrototypeJson($json);

                if ($format === 'json') {
                    if (empty($result)) {
                        $output->writeln(json_encode([
                            'status' => 'success',
                            'file' => $json,
                            'schema' => realpath(\MultiFlexi\CredentialProtoType::$credTypeSchema),
                            'message' => 'JSON is valid',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'file' => $json,
                            'violations' => $result,
                            'schema' => realpath(\MultiFlexi\CredentialProtoType::$credTypeSchema),
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

            case 'export-json':
                $id = $input->getOption('id');
                $uuid = $input->getOption('uuid');
                $code = $input->getOption('code');
                $file = $input->getOption('file');

                if (empty($id) && empty($uuid) && empty($code)) {
                    $output->writeln('<error>Missing --id, --uuid, or --code for crprototype export-json</error>');
                    return MultiFlexiCommand::FAILURE;
                }

                if (empty($file)) {
                    $output->writeln('<error>Missing --file for export-json</error>');
                    return MultiFlexiCommand::FAILURE;
                }

                $credProto = new \MultiFlexi\CredentialProtoType();

                // Find by UUID or code first
                if (!empty($uuid)) {
                    $found = $credProto->listingQuery()->where(['uuid' => $uuid])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given UUID</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                } elseif (!empty($code)) {
                    $found = $credProto->listingQuery()->where(['code' => $code])->fetch();
                    if (!$found) {
                        $output->writeln('<error>No credential prototype found with given code</error>');
                        return MultiFlexiCommand::FAILURE;
                    }
                    $id = $found['id'];
                }

                $credProto = new \MultiFlexi\CredentialProtoType((int) $id);
                $data = $credProto->getData();

                // Create export structure - this is simplified, full implementation would include fields and translations
                $exportData = [
                    'uuid' => $data['uuid'],
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                    'version' => $data['version'] ?? '1.0',
                    'logo' => $data['logo'] ?? null,
                    'url' => $data['url'] ?? null,
                    'fields' => [], // This would need to be populated from credential_prototype_field table
                ];

                $jsonContent = json_encode($exportData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
                $result = file_put_contents($file, $jsonContent);

                if ($result !== false) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'success',
                            'message' => 'Credential prototype exported successfully',
                            'file' => $file,
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<info>Credential prototype exported to: '.$file.'</info>');
                    }
                    return MultiFlexiCommand::SUCCESS;
                }

                if ($format === 'json') {
                    $output->writeln(json_encode([
                        'status' => 'error',
                        'message' => 'Failed to export credential prototype',
                        'file' => $file,
                    ], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>Failed to export credential prototype to: '.$file.'</error>');
                }
                return MultiFlexiCommand::FAILURE;

            default:
                $output->writeln("<error>Unknown action: {$action}</error>");
                return MultiFlexiCommand::FAILURE;
        }
    }

    /**
     * Scan filesystem for credential prototype classes and return them as array.
     *
     * @return array<array<string,mixed>>
     */
    private function getFilesystemCredentialPrototypes(): array
    {
        $prototypes = [];
        $credentialTypeDir = dirname(__DIR__, 3) . '/php-vitexsoftware-multiflexi-core/src/MultiFlexi/CredentialType';
        
        if (!is_dir($credentialTypeDir)) {
            return $prototypes;
        }

        $files = glob($credentialTypeDir . '/*.php');
        if ($files === false) {
            return $prototypes;
        }

        foreach ($files as $file) {
            $className = basename($file, '.php');
            
            // Skip Common.php as it's likely a base class
            if ($className === 'Common') {
                continue;
            }
            
            $fullClassName = "\\MultiFlexi\\CredentialType\\{$className}";
            
            try {
                if (class_exists($fullClassName)) {
                    $reflection = new \ReflectionClass($fullClassName);
                    
                    // Check if class implements credentialTypeInterface
                    if ($reflection->implementsInterface('\\MultiFlexi\\credentialTypeInterface')) {
                        $prototypes[] = [
                            'id' => 'fs_' . strtolower($className), // Filesystem prefix to distinguish
                            'uuid' => $fullClassName::uuid(),
                            'code' => $className,
                            'name' => $fullClassName::name(),
                            'description' => $fullClassName::description(),
                            'version' => '1.0', // Default version for filesystem classes
                            'url' => '',
                            'logo' => $fullClassName::logo(),
                            'created_at' => 'N/A (Filesystem)',
                            'updated_at' => 'N/A (Filesystem)',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Skip classes that can't be loaded or don't have required methods
                continue;
            }
        }

        return $prototypes;
    }
}
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

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\CredentialProtoType;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends MultiFlexiCommand
{
    /**
     * @return array<string> errors
     */
    protected function validateCredPrototypeJson(string $jsonFile): array
    {
        return self::validateJson($jsonFile, CredentialProtoType::$credProtoTypeSchema);
    }

    /**
     * @return array<array<string, mixed>>
     */
    protected static function getFilesystemCredentialPrototypes(): array
    {
        $prototypes = [];
        $credentialTypeDir = \dirname(__DIR__, 4).'/php-vitexsoftware-multiflexi-core/src/MultiFlexi/CredentialType';

        if (!is_dir($credentialTypeDir)) {
            return $prototypes;
        }

        $files = glob($credentialTypeDir.'/*.php');

        if ($files === false) {
            return $prototypes;
        }

        foreach ($files as $file) {
            $className = basename($file, '.php');

            if ($className === 'Common') {
                continue;
            }

            $fullClassName = "\\MultiFlexi\\CredentialType\\{$className}";

            try {
                if (class_exists($fullClassName)) {
                    $reflection = new \ReflectionClass($fullClassName);

                    if ($reflection->implementsInterface('\\MultiFlexi\\credentialTypeInterface')) {
                        $instance = new $fullClassName();
                        $prototypes[] = [
                            'id' => 'fs_'.strtolower($className),
                            'uuid' => $instance->uuid(),
                            'code' => $className,
                            'name' => $instance->name(),
                            'description' => $instance->description(),
                            'version' => '1.0',
                            'url' => '',
                            'logo' => $instance->logo(),
                            'created_at' => 'N/A (Filesystem)',
                            'updated_at' => 'N/A (Filesystem)',
                        ];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $prototypes;
    }

    protected static function getLocalizedString(string $key, string $locale = 'cs_CZ'): string
    {
        $i18nDir = '/home/vitex/Projects/Multi/MultiFlexi/i18n';
        $domain = 'multiflexi';

        if (!is_dir($i18nDir)) {
            return $key;
        }

        $originalLocale = setlocale(\LC_MESSAGES, null);

        try {
            if (setlocale(\LC_MESSAGES, $locale) === false) {
                return $key;
            }

            if (bindtextdomain($domain, $i18nDir) === false) {
                return $key;
            }

            if (textdomain($domain) === false) {
                return $key;
            }

            $translated = gettext($key);

            return ($translated !== $key) ? $translated : $key;
        } finally {
            setlocale(\LC_MESSAGES, $originalLocale);
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected static function normalizePrototypeJson(array $data, string $primaryLocale = 'en', string $fallbackLocale = 'cs'): array
    {
        if (!isset($data['version']) || !\is_string($data['version']) || $data['version'] === '') {
            $data['version'] = '1.0';
        }

        $extract = static function ($value) use ($primaryLocale, $fallbackLocale) {
            if (\is_array($value)) {
                if (isset($value[$primaryLocale]) && \is_string($value[$primaryLocale])) {
                    return $value[$primaryLocale];
                }

                if (isset($value[$fallbackLocale]) && \is_string($value[$fallbackLocale])) {
                    return $value[$fallbackLocale];
                }

                foreach ($value as $v) {
                    if (\is_string($v)) {
                        return $v;
                    }
                }

                return '';
            }

            return \is_string($value) ? $value : '';
        };

        if (isset($data['name'])) {
            $data['name'] = $extract($data['name']);
        }

        if (isset($data['description'])) {
            $data['description'] = $extract($data['description']);
        }

        if (isset($data['fields']) && \is_array($data['fields'])) {
            foreach ($data['fields'] as $idx => $field) {
                if (isset($field['name'])) {
                    $data['fields'][$idx]['name'] = $extract($field['name']);
                }

                if (isset($field['description'])) {
                    $data['fields'][$idx]['description'] = $extract($field['description']);
                }
            }
        }

        return $data;
    }

    protected static function syncPrototypeFields(CredentialProtoType $credProto, string $fullClassName, OutputInterface $output): void
    {
        try {
            $instance = new $fullClassName();

            if (!method_exists($instance, 'fieldsProvided')) {
                return;
            }

            $configFields = $instance->fieldsProvided();

            if (empty($configFields)) {
                return;
            }

            $fieldsData = $configFields->getFields();

            if (empty($fieldsData)) {
                return;
            }

            $existingFields = [];
            $fieldEngine = new \MultiFlexi\CredentialProtoTypeField();
            $fieldResults = $fieldEngine->listingQuery()
                ->where(['credential_prototype_id' => $credProto->getMyKey()])
                ->fetchAll();

            foreach ($fieldResults as $field) {
                $existingFields[$field['keyword']] = $field;
            }

            $fieldsProcessed = [];

            foreach ($fieldsData as $fieldName => $fieldObject) {
                $fieldsProcessed[] = $fieldName;
                $fieldData = [
                    'credential_prototype_id' => $credProto->getMyKey(),
                    'keyword' => $fieldName,
                    'type' => method_exists($fieldObject, 'getType') ? $fieldObject->getType() : 'string',
                    'name' => method_exists($fieldObject, 'getName') ? self::getLocalizedString($fieldObject->getName()) : $fieldName,
                    'description' => method_exists($fieldObject, 'getDescription') ? self::getLocalizedString($fieldObject->getDescription()) : '',
                    'hint' => method_exists($fieldObject, 'getHint') ? $fieldObject->getHint() : null,
                    'default_value' => method_exists($fieldObject, 'getValue') ? $fieldObject->getValue() : null,
                    'required' => method_exists($fieldObject, 'isRequired') ? (bool) $fieldObject->isRequired() : false,
                    'options' => '{}',
                ];

                if (isset($existingFields[$fieldName])) {
                    $fe = new \MultiFlexi\CredentialProtoTypeField($existingFields[$fieldName]['id']);
                    $fe->setData($fieldData);
                    $fe->saveToSQL();
                } else {
                    $fe = new \MultiFlexi\CredentialProtoTypeField();
                    $fe->setData($fieldData);
                    $fe->insertToSQL();
                }
            }

            foreach ($existingFields as $fieldName => $fieldData) {
                if (!\in_array($fieldName, $fieldsProcessed, true)) {
                    (new \MultiFlexi\CredentialProtoTypeField($fieldData['id']))->deleteFromSQL();
                    $output->writeln("<comment>Removed obsolete field: {$fieldName}</comment>");
                }
            }
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to sync fields for {$fullClassName}: ".$e->getMessage().'</error>');
        }
    }
}

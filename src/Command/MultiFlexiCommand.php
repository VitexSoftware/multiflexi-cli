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

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of Command.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
abstract class MultiFlexiCommand extends \Symfony\Component\Console\Command\Command
{
    public function listing(): array
    {
        return [];
    }

    /**
     * Output a table using LucidFrame\Console\ConsoleTable.
     *
     * @param array $data The data to display as a table (array of associative arrays)
     */
    public static function outputTable(array $data, int $cellMaxLength = 50, array $header = []): string
    {
        $table = new \LucidFrame\Console\ConsoleTable();

        // Check for empty or invalid data
        if (empty($data) || !\is_array(reset($data))) {
            echo _('No data')."\n";

            return '';
        }

        $headers = $header ?: array_keys(reset($data));

        // Add header row with columns so it matches the size of data rows
        foreach ($headers as $i => $column) {
            if ($i === 0) {
                $table->addHeader($column);
            } else {
                $table->addColumn($column);
            }
        }

        // Add data rows
        foreach ($data as $row) {
            $table->addRow();

            foreach ($row as $cell) {
                $cellString = (string) $cell;

                if (mb_strlen($cellString) > $cellMaxLength) {
                    $truncatedCell = mb_substr($cellString, 0, $cellMaxLength - 3).'🪚';
                } else {
                    $truncatedCell = $cellString;
                }

                $table->addColumn($truncatedCell);
            }
        }

        return $table->getTable();
    }

    /**
     * Unified result output for all commands (create, update, delete).
     *
     * @param OutputInterface $output
     * @param array           $shortResult e.g. ["updated"=>true, "company_id"=>1]
     * @param array           $fullRecord  full associative array of the record (optional)
     * @param string          $format      'json' or 'text'
     * @param bool            $verbose     true to print full record
     */
    public function outputResult($output, array $shortResult, ?array $fullRecord = null, string $format = 'text', bool $verbose = false): void
    {
        if ($verbose && $fullRecord) {
            if ($format === 'json') {
                $output->writeln(json_encode($fullRecord, \JSON_PRETTY_PRINT));
            } else {
                foreach ($fullRecord as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode($shortResult, \JSON_PRETTY_PRINT));
            } else {
                foreach ($shortResult as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        }
    }

    /**
     * valid filename for current App Json.
     *
     * @return string
     */
    public function jsonFileName()
    {
        return strtolower(trim(preg_replace('#\W+#', '_', (string) $this->getRecordName()), '_')).'.multiflexi.'.strtolower(\Ease\Functions::baseClassName($this)).'.json';
    }

    /**
     * @return array<string> errors
     */
    public static function validateJson(string $jsonFile, string $schemaFile): array
    {
        $violations = [];
        
        // Validate input file exists and is readable
        if (!is_file($jsonFile)) {
            $violations[] = "JSON file does not exist or is not a file: {$jsonFile}";
            return $violations;
        }
        
        if (!is_readable($jsonFile)) {
            $violations[] = "JSON file is not readable: {$jsonFile}";
            return $violations;
        }
        
        // Validate schema file exists and is readable
        if (!is_file($schemaFile)) {
            $violations[] = "Schema file does not exist or is not a file: {$schemaFile}";
            return $violations;
        }
        
        if (!is_readable($schemaFile)) {
            $violations[] = "Schema file is not readable: {$schemaFile}";
            return $violations;
        }
        
        // Read and validate JSON content
        $jsonContent = file_get_contents($jsonFile);
        if ($jsonContent === false) {
            $violations[] = "Failed to read JSON file: {$jsonFile}";
            return $violations;
        }
        
        $data = json_decode($jsonContent);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $violations[] = "Invalid JSON in file {$jsonFile}: " . json_last_error_msg();
            return $violations;
        }
        
        // Resolve schema path
        $resolvedSchemaPath = realpath($schemaFile);
        if ($resolvedSchemaPath === false) {
            $violations[] = "Failed to resolve schema file path: {$schemaFile}";
            return $violations;
        }
        
        // Validate with JSON Schema
        try {
            $validator = new \JsonSchema\Validator();
            $validator->validate($data, (object) ['$ref' => 'file://' . $resolvedSchemaPath]);

            if (!$validator->isValid()) {
                foreach ($validator->getErrors() as $error) {
                    $violations[] = sprintf("[%s] %s\n", $error['property'], $error['message']);
                }
            }
        } catch (\Exception $e) {
            $violations[] = "JSON Schema validation failed: " . $e->getMessage();
        }

        return $violations;
    }

    public function readFileStrict(string $path): string
    {
        if (!is_file($path)) {
            throw new \RuntimeException("File does not exist: {$path}");
        }

        if (!is_readable($path)) {
            throw new \RuntimeException("File is not readable: {$path}");
        }

        $fh = fopen($path, 'rb');

        if ($fh === false) {
            throw new \RuntimeException("Unable to open file: {$path}");
        }

        $data = '';

        while (!feof($fh)) {
            $chunk = fread($fh, 8192);

            if ($chunk === false) {
                fclose($fh);

                throw new \RuntimeException("Read error: {$path}");
            }
        }

        fclose($fh);

        return $data;
    }

    /**
     * Output a JSON error response with status and message fields.
     */
    protected function jsonError(OutputInterface $output, string $message, string $status = 'error'): void
    {
        $output->writeln(json_encode([
            'status' => $status,
            'message' => $message,
        ], \JSON_PRETTY_PRINT));
    }

    /**
     * Output a JSON success response with status and message fields, plus extra data.
     */
    protected function jsonSuccess(OutputInterface $output, string $message = 'OK', array $data = []): void
    {
        $output->writeln(json_encode(array_merge([
            'status' => 'success',
            'message' => $message,
        ], $data), \JSON_PRETTY_PRINT));
    }

    /**
     * Convert string option to boolean if needed.
     *
     * @param mixed $val
     *
     * @return null|bool
     */
    protected function parseBoolOption($val)
    {
        if (\is_bool($val)) {
            return $val;
        }

        if (null === $val) {
            return null;
        }

        $val = strtolower((string) $val);

        return \in_array($val, ['1', 'true', 'yes', 'on'], true);
    }
}

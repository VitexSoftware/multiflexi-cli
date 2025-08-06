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
    public function outputTable(array $data): void
    {
        if (empty($data)) {
            echo _('No data')."\n";

            return;
        }

        $table = new \LucidFrame\Console\ConsoleTable();

        $headers = array_keys(reset($data));

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
                $table->addColumn($cell);
            }
        }

        $table->display();
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

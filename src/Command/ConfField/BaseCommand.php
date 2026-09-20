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

namespace MultiFlexi\Cli\Command\ConfField;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Conffield;

/**
 * Shared helpers for conf-field CLI commands.
 */
abstract class BaseCommand extends MultiFlexiCommand
{
    /** @var list<string> */
    public const TYPES = [
        'string', 'text', 'integer', 'float', 'email', 'url', 'password', 'bool', 'file-path', 'set',
    ];

    protected static function boolFlag($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (\is_bool($value)) {
            return $value ? 1 : 0;
        }

        $v = strtolower(trim((string) $value));

        return \in_array($v, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function loadField(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = (new Conffield($id, ['autoload' => true]))->getData();

        return empty($row) ? null : $row;
    }
}

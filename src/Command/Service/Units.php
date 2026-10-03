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

namespace MultiFlexi\Cli\Command\Service;

/**
 * Maps short status-line names to systemd units.
 *
 * Housekeeper is controlled via its timer (the long-lived unit); the oneshot
 * .service is only used for an immediate run.
 */
final class Units
{
    /** @var array<string, string> */
    public const MAP = [
        'executor' => 'multiflexi-executor.service',
        'scheduler' => 'multiflexi-scheduler.service',
        'housekeeper' => 'multiflexi-housekeeper.timer',
    ];

    public static function resolve(string $name): ?string
    {
        $key = strtolower(trim($name));

        return self::MAP[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::MAP);
    }
}

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

namespace MultiFlexi\Cli\Command\Encryption;

use MultiFlexi\Cli\Command\MultiFlexiCommand;

abstract class BaseCommand extends MultiFlexiCommand
{
    protected static function getMasterKey(): ?string
    {
        $masterKey = getenv('ENCRYPTION_MASTER_KEY');

        if ($masterKey) {
            return $masterKey;
        }

        $masterKey = getenv('MULTIFLEXI_MASTER_KEY');

        if ($masterKey) {
            return $masterKey;
        }

        return \Ease\Shared::cfg('ENCRYPTION_MASTER_KEY');
    }
}

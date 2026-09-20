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

use MultiFlexi\Configuration;
use MultiFlexi\Conffield;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends BaseCommand
{
    protected static $defaultName = 'conffield:delete';

    protected function configure(): void
    {
        $this
            ->setName('conffield:delete')
            ->setDescription('Delete a configuration field')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Configuration field ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $id = (int) $input->getOption('id');
        $existing = self::loadField($id);

        if ($existing === null) {
            $msg = "Configuration field #{$id} not found";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $appId = (int) ($existing['app_id'] ?? 0);
        $keyname = (string) ($existing['keyname'] ?? '');

        // Remove stored configuration values that use this field (same as web UI).
        $removed = 0;

        if ($appId > 0 && $keyname !== '') {
            $removed = (new Configuration())->deleteFromSQL(['app_id' => $appId, 'name' => $keyname]);
        }

        $field = new Conffield($id, ['autoload' => true]);

        if (!$field->deleteFromSQL($id)) {
            $msg = 'Failed to delete configuration field';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        if ($format === 'json') {
            $this->jsonSuccess($output, 'Configuration field deleted', [
                'id' => $id,
                'keyname' => $keyname,
                'removed_configurations' => (int) $removed,
            ]);
        } else {
            $output->writeln(sprintf('<info>Configuration field #%d (%s) deleted</info>', $id, $keyname));

            if ($removed) {
                $output->writeln(sprintf('<comment>%d used configuration value(s) removed</comment>', $removed));
            }
        }

        return self::SUCCESS;
    }
}

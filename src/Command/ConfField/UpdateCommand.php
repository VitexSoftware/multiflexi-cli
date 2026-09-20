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

use MultiFlexi\Conffield;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends BaseCommand
{
    protected static $defaultName = 'conffield:update';

    protected function configure(): void
    {
        $this
            ->setName('conffield:update')
            ->setDescription('Update a configuration field')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Configuration field ID')
            ->addOption('keyname', null, InputOption::VALUE_OPTIONAL, 'Environment / config keyword')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Field type')
            ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Description')
            ->addOption('hint', null, InputOption::VALUE_OPTIONAL, 'Hint')
            ->addOption('note', null, InputOption::VALUE_OPTIONAL, 'Note')
            ->addOption('defval', null, InputOption::VALUE_OPTIONAL, 'Default value')
            ->addOption('required', null, InputOption::VALUE_OPTIONAL, 'Required (true/false)')
            ->addOption('secret', null, InputOption::VALUE_OPTIONAL, 'Secret (true/false)')
            ->addOption('multiline', null, InputOption::VALUE_OPTIONAL, 'Multiline (true/false)')
            ->addOption('expiring', null, InputOption::VALUE_OPTIONAL, 'Expiring (true/false)');
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

        $type = $input->getOption('type');

        if ($type !== null && $type !== '' && !\in_array((string) $type, self::TYPES, true)) {
            $msg = 'Invalid --type. Allowed: '.implode(', ', self::TYPES);
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $data = $existing;
        $data['id'] = $id;

        foreach (['keyname', 'type', 'description', 'hint', 'note', 'defval'] as $opt) {
            $val = $input->getOption($opt);

            if ($val !== null) {
                $data[$opt] = $val;
            }
        }

        foreach (['required', 'secret', 'multiline', 'expiring'] as $opt) {
            $val = $input->getOption($opt);

            if ($val !== null) {
                $data[$opt] = self::boolFlag($val);
            }
        }

        $field = new Conffield($id, ['autoload' => true]);

        if (!$field->takeData($data) || $field->dbsync() === null) {
            $msg = 'Failed to update configuration field';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $row = $field->getData();

        if ($format === 'json') {
            $this->jsonSuccess($output, 'Configuration field updated', $row);
        } else {
            $output->writeln('<info>Configuration field updated</info>');
            $output->writeln(self::outputTable([$row]));
        }

        return self::SUCCESS;
    }
}

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

use MultiFlexi\Application;
use MultiFlexi\Conffield;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends BaseCommand
{
    protected static $defaultName = 'conffield:create';

    protected function configure(): void
    {
        $this
            ->setName('conffield:create')
            ->setDescription('Create a configuration field for an application')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('keyname', null, InputOption::VALUE_REQUIRED, 'Environment / config keyword')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Field type (string, text, integer, float, email, url, password, bool, file-path, set)')
            ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Description', '')
            ->addOption('hint', null, InputOption::VALUE_OPTIONAL, 'Hint', '')
            ->addOption('note', null, InputOption::VALUE_OPTIONAL, 'Note', '')
            ->addOption('defval', null, InputOption::VALUE_OPTIONAL, 'Default value', '')
            ->addOption('required', null, InputOption::VALUE_OPTIONAL, 'Required (true/false)', 'false')
            ->addOption('secret', null, InputOption::VALUE_OPTIONAL, 'Secret (true/false)', 'false')
            ->addOption('multiline', null, InputOption::VALUE_OPTIONAL, 'Multiline (true/false)', 'false')
            ->addOption('expiring', null, InputOption::VALUE_OPTIONAL, 'Expiring (true/false)', 'false');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $appId = (int) $input->getOption('app_id');
        $keyname = trim((string) $input->getOption('keyname'));
        $type = trim((string) $input->getOption('type'));

        if ($appId <= 0 || $keyname === '' || $type === '') {
            $msg = 'Missing required --app_id, --keyname, or --type';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        if (!\in_array($type, self::TYPES, true)) {
            $msg = 'Invalid --type. Allowed: '.implode(', ', self::TYPES);
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $app = new Application($appId);

        if (empty($app->getData())) {
            $msg = "Application #{$appId} not found";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $field = new Conffield();
        $ok = $field->addAppConfig($appId, $keyname, [
            'type' => $type,
            'description' => (string) $input->getOption('description'),
            'hint' => (string) $input->getOption('hint'),
            'note' => (string) $input->getOption('note'),
            'defval' => (string) $input->getOption('defval'),
            'required' => self::boolFlag($input->getOption('required')),
            'secret' => self::boolFlag($input->getOption('secret')),
            'multiline' => self::boolFlag($input->getOption('multiline')),
            'expiring' => self::boolFlag($input->getOption('expiring')),
        ]);

        if (!$ok) {
            $msg = 'Failed to create configuration field';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $row = $field->getData();

        if ($format === 'json') {
            $this->jsonSuccess($output, 'Configuration field created', $row);
        } else {
            $output->writeln('<info>Configuration field created</info>');
            $output->writeln(self::outputTable([$row]));
        }

        return self::SUCCESS;
    }
}

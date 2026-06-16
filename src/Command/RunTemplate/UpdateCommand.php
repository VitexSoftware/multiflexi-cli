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

namespace MultiFlexi\Cli\Command\RunTemplate;

use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:update';

    protected function configure(): void
    {
        $this
            ->setName('run-template:update')
            ->setDescription('Update a run template')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'App ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'App UUID')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('interv', null, InputOption::VALUE_REQUIRED, 'Interval code')
            ->addOption('cron', null, InputOption::VALUE_OPTIONAL, 'Crontab expression')
            ->addOption('active', null, InputOption::VALUE_REQUIRED, 'Active')
            ->addOption('executor', null, InputOption::VALUE_REQUIRED, 'Executor')
            ->addOption('config', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Config key=value (repeatable)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $rt = new RunTemplate((int) $id);
        $data = [];

        foreach (['name', 'app_id', 'company_id', 'interv', 'cron', 'active', 'executor'] as $field) {
            $val = $input->getOption($field);

            if ($field === 'cron' && $val !== null && !$this->isValidCronExpression($val)) {
                if ($format === 'json') {
                    $output->writeln(json_encode(['status' => 'error', 'message' => 'Invalid crontab expression'], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>Invalid crontab expression: '.$val.'</error>');
                }

                return self::FAILURE;
            }

            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        $appUuid = $input->getOption('app_uuid');

        if ($appUuid !== null) {
            $pdo = (new RunTemplate())->getFluentPDO()->getPdo();
            $stmt = $pdo->prepare('SELECT id FROM apps WHERE uuid = :uuid');
            $stmt->execute(['uuid' => $appUuid]);
            $row = $stmt->fetch();

            if ($row && isset($row['id'])) {
                $data['app_id'] = $row['id'];
            } else {
                $output->writeln('<error>Application with given UUID not found</error>');

                return self::FAILURE;
            }
        }

        $overridedEnv = $this->buildOverridedEnv($input);
        $rt->setRuntemplateEnvironment($overridedEnv);

        if (!empty($data)) {
            try {
                $rt->updateToSQL($data, ['id' => $id]);
            } catch (\Exception $e) {
                $output->writeln('<error>Failed to update run template: '.$e->getMessage().'</error>');

                return self::FAILURE;
            }
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $rt->loadFromSQL(['id' => $id]);
            $full = $rt->getData();

            if ($format === 'json') {
                $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['runtemplate_id' => $id, 'updated' => true], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("RunTemplate updated successfully (ID: {$id})");
            }
        }

        return self::SUCCESS;
    }
}

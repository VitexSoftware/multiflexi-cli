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

class CreateCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create a run template')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'App ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'App UUID')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('company', null, InputOption::VALUE_REQUIRED, 'Company slug or ID')
            ->addOption('interv', null, InputOption::VALUE_REQUIRED, 'Interval code')
            ->addOption('cron', null, InputOption::VALUE_OPTIONAL, 'Crontab expression')
            ->addOption('active', null, InputOption::VALUE_REQUIRED, 'Active')
            ->addOption('executor', null, InputOption::VALUE_REQUIRED, 'Executor')
            ->addOption('config', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Config key=value (repeatable)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));

        $companyOption = $input->getOption('company');

        if ($companyOption !== null) {
            if (is_numeric($companyOption)) {
                $input->setOption('company_id', (int) $companyOption);
            } else {
                $found = (new \MultiFlexi\Company())->listingQuery()->where(['slug' => $companyOption])->fetch();

                if (!$found) {
                    $output->writeln('<error>Company not found for slug: '.$companyOption.'</error>');

                    return self::FAILURE;
                }

                $input->setOption('company_id', (int) $found['id']);
            }
        }

        $data = [];

        foreach (['name', 'app_id', 'company_id', 'interv', 'cron', 'active', 'executor'] as $field) {
            $val = $input->getOption($field);

            if ($field === 'cron' && $val !== null && !$this->isValidCronExpression($val)) {
                if ($format === 'json') {
                    $output->writeln(json_encode(['status' => 'error', 'message' => 'Invalid crontab expression', 'cron' => $val], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>Invalid crontab expression: '.$val.'</error>');
                }

                return self::FAILURE;
            }

            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        if (empty($data['interv'])) {
            $data['interv'] = 'n';
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
                if ($format === 'json') {
                    $output->writeln(json_encode(['status' => 'error', 'message' => 'Application with given UUID not found'], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>Application with given UUID not found: '.$appUuid.'</error>');
                }

                return self::FAILURE;
            }
        }

        if (empty($data['name']) || empty($data['app_id']) || empty($data['company_id'])) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing --name, --app_id or --company_id'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing --name, --app_id or --company_id</error>');
            }

            return self::FAILURE;
        }

        $rt = new RunTemplate();
        $rt->takeData($data);
        $rt->saveToSQL();
        $rtId = $rt->getMyKey();

        $overridedEnv = $this->buildOverridedEnv($input);
        $rt->setRuntemplateEnvironment($overridedEnv);

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = (new RunTemplate((int) $rtId))->getData();

            if ($format === 'json') {
                $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['runtemplate_id' => $rtId, 'created' => true], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("RunTemplate created with ID: {$rtId}");
            }
        }

        return self::SUCCESS;
    }
}

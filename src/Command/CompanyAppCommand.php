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

use MultiFlexi\Application;
use MultiFlexi\Company;
use MultiFlexi\CompanyApp;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompanyAppCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'companyapp';

    protected function configure(): void
    {
        $this
            ->setName('companyapp')
            ->setDescription('Manage company-application relations')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|assign|unassign')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Relation ID')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'Application UUID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order: A (ascending) or D (descending)')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset for pagination')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of fields to display');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        $companyId = $input->getOption('company_id');
        $appId = $input->getOption('app_id');
        $appUuid = $input->getOption('app_uuid');

        // Resolve app UUID to ID if needed
        if (!empty($appUuid) && empty($appId)) {
            $found = (new Application())->listingQuery()->where(['uuid' => $appUuid])->fetch();

            if (!$found) {
                $msg = 'No application found with given UUID';
                $format === 'json'
                    ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                    : $output->writeln("<error>{$msg}</error>");

                return Command::FAILURE;
            }

            $appId = $found['id'];
        }

        switch ($action) {
            case 'list':
                if (empty($companyId) || empty($appId)) {
                    $msg = '--company_id and either --app_id or --app_uuid are required for list.';
                    $format === 'json'
                        ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                        : $output->writeln("<error>{$msg}</error>");

                    return Command::FAILURE;
                }

                $query = (new RunTemplate())->listingQuery()->where([
                    'company_id' => $companyId,
                    'app_id' => $appId,
                ]);

                $order = $input->getOption('order');

                if (!empty($order)) {
                    $query = $query->orderBy('id '.(strtoupper($order) === 'D' ? 'DESC' : 'ASC'));
                }

                $limit = $input->getOption('limit');

                if (!empty($limit) && is_numeric($limit)) {
                    $query = $query->limit((int) $limit);
                }

                $offset = $input->getOption('offset');

                if (!empty($offset) && is_numeric($offset)) {
                    $query = $query->offset((int) $offset);
                }

                $runtemplates = $query->fetchAll();

                $fields = $input->getOption('fields');

                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $runtemplates = array_map(static function ($rt) use ($fieldList) {
                        return array_intersect_key($rt, array_flip($fieldList));
                    }, $runtemplates);
                }

                $format === 'json'
                    ? $output->writeln(json_encode($runtemplates, \JSON_PRETTY_PRINT))
                    : $output->writeln(self::outputTable($runtemplates));

                return Command::SUCCESS;

            case 'assign':
                if (empty($companyId) || empty($appId)) {
                    $msg = '--company_id and either --app_id or --app_uuid are required for assign.';
                    $format === 'json'
                        ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                        : $output->writeln("<error>{$msg}</error>");

                    return Command::FAILURE;
                }

                $company = new Company((int) $companyId);

                if (empty($company->getData())) {
                    $msg = "Company ID {$companyId} not found.";
                    $format === 'json'
                        ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                        : $output->writeln("<error>{$msg}</error>");

                    return Command::FAILURE;
                }

                // Check if already assigned
                $existing = (new CompanyApp())->listingQuery()
                    ->where(['company_id' => $companyId, 'app_id' => $appId])
                    ->fetch();

                if ($existing) {
                    $result = ['status' => 'ok', 'message' => 'Already assigned', 'company_id' => (int) $companyId, 'app_id' => (int) $appId];
                    $format === 'json'
                        ? $output->writeln(json_encode($result, \JSON_PRETTY_PRINT))
                        : $output->writeln("Application {$appId} already assigned to company {$companyId}");

                    return Command::SUCCESS;
                }

                // Insert companyapp record
                $companyApp = new CompanyApp();
                $companyApp->insertToSQL(['company_id' => (int) $companyId, 'app_id' => (int) $appId]);

                // Create a default RunTemplate
                $allApps = (new Application())->listingQuery()->select(['id', 'name'], true)->fetchAll('id');
                $appName = isset($allApps[$appId]) ? $allApps[$appId]['name'] : (string) $appId;

                $runTemplate = new RunTemplate();
                $runTemplate->setDataValue('app_id', (int) $appId);
                $runTemplate->setDataValue('company_id', (int) $companyId);
                $runTemplate->setDataValue('interv', 'n');
                $runTemplate->setDataValue('name', $appName);
                $rtId = $runTemplate->insertToSQL();

                $result = [
                    'status' => 'success',
                    'message' => 'Application assigned to company',
                    'company_id' => (int) $companyId,
                    'app_id' => (int) $appId,
                    'runtemplate_id' => $rtId,
                ];
                $format === 'json'
                    ? $output->writeln(json_encode($result, \JSON_PRETTY_PRINT))
                    : $output->writeln("Application {$appId} ({$appName}) assigned to company {$companyId}, RunTemplate {$rtId} created.");

                return Command::SUCCESS;

            case 'unassign':
                if (empty($companyId) || empty($appId)) {
                    $msg = '--company_id and either --app_id or --app_uuid are required for unassign.';
                    $format === 'json'
                        ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                        : $output->writeln("<error>{$msg}</error>");

                    return Command::FAILURE;
                }

                // Remove action configs and run templates
                $runTemplate = new RunTemplate();
                $appRuntemplates = $runTemplate->listingQuery()
                    ->where('company_id', $companyId)
                    ->where('app_id', $appId)
                    ->fetchAll();

                $removedRuntemplates = [];

                foreach ($appRuntemplates as $rtData) {
                    $runTemplate->getFluentPDO()
                        ->deleteFrom('actionconfig')
                        ->where('runtemplate_id', $rtData['id'])
                        ->execute();
                    $removedRuntemplates[] = $rtData['id'];
                }

                $runTemplate->deleteFromSQL(['app_id' => $appId, 'company_id' => $companyId]);

                // Remove companyapp record
                $companyApp = new CompanyApp();
                $companyApp->deleteFromSQL(['company_id' => $companyId, 'app_id' => $appId]);

                $result = [
                    'status' => 'success',
                    'message' => 'Application unassigned from company',
                    'company_id' => (int) $companyId,
                    'app_id' => (int) $appId,
                    'removed_runtemplates' => $removedRuntemplates,
                ];
                $format === 'json'
                    ? $output->writeln(json_encode($result, \JSON_PRETTY_PRINT))
                    : $output->writeln("Application {$appId} unassigned from company {$companyId}. Removed RunTemplates: ".implode(', ', $removedRuntemplates));

                return Command::SUCCESS;

            default:
                $msg = "Unknown action: {$action}. Supported: list, assign, unassign";
                $format === 'json'
                    ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                    : $output->writeln("<error>{$msg}</error>");

                return Command::FAILURE;
        }
    }
}

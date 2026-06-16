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

namespace MultiFlexi\Cli\Command\CompanyApp;

use MultiFlexi\Application;
use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\CompanyApp;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnassignCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'company-app:unassign';

    protected function configure(): void
    {
        $this
            ->setName('company-app:unassign')
            ->setDescription('Unassign an application from a company, removing RunTemplates and action configs')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'Application ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'Application UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $companyId = $input->getOption('company_id');
        $appId = $input->getOption('app_id');
        $appUuid = $input->getOption('app_uuid');

        if (!empty($appUuid) && empty($appId)) {
            $found = (new Application())->listingQuery()->where(['uuid' => $appUuid])->fetch();

            if (!$found) {
                $msg = 'No application found with given UUID';
                $format === 'json'
                    ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                    : $output->writeln("<error>{$msg}</error>");

                return self::FAILURE;
            }

            $appId = $found['id'];
        }

        if (empty($companyId) || empty($appId)) {
            $msg = '--company_id and either --app_id or --app_uuid are required.';
            $format === 'json'
                ? $output->writeln(json_encode(['status' => 'error', 'message' => $msg], \JSON_PRETTY_PRINT))
                : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

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

        return self::SUCCESS;
    }
}

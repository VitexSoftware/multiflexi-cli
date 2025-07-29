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

use MultiFlexi\ConfigField;
use MultiFlexi\ConfigFields;
use MultiFlexi\Job;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of RunTemplateCommand.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
// Přidání RunTemplateCommand pro správu runtemplate
class RunTemplateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'runtemplate';
    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Manage runtemplates')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|create|update|delete|schedule')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('app_id', null, InputOption::VALUE_REQUIRED, 'App ID')
            ->addOption('app_uuid', null, InputOption::VALUE_REQUIRED, 'App UUID')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('company', null, InputOption::VALUE_REQUIRED, 'Company slug (string) or ID (integer)')
            ->addOption('interv', null, InputOption::VALUE_REQUIRED, 'Interval code')
            ->addOption('active', null, InputOption::VALUE_REQUIRED, 'Active')
            ->addOption('config', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Application config key=value (repeatable)')
            ->addOption('schedule_time', null, InputOption::VALUE_OPTIONAL, 'Schedule time for launch (Y-m-d H:i:s or "now")', 'now')
            ->addOption('executor', null, InputOption::VALUE_OPTIONAL, 'Executor to use for launch', 'Native')
            ->addOption('env', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Environment override key=value (repeatable)')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of fields to display');
        // Add more options as needed
    }

    protected function parseConfigOptions(InputInterface $input): array
    {
        $configs = $input->getOption('config') ?? [];
        $result = [];

        foreach ($configs as $item) {
            if (str_contains($item, '=')) {
                [$key, $value] = explode('=', $item, 2);
                $result[$key] = $value;
            }
        }

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        // Resolve company_id from --company if provided
        $companyOption = $input->getOption('company');

        if ($companyOption !== null) {
            $companyId = null;

            if (is_numeric($companyOption)) {
                $companyId = (int) $companyOption;
            } else {
                // Lookup company by slug
                $companyObj = new \MultiFlexi\Company();
                $found = $companyObj->listingQuery()->where(['slug' => $companyOption])->fetch();

                if ($found && isset($found['id'])) {
                    $companyId = (int) $found['id'];
                } else {
                    $output->writeln('<error>Company not found for slug: '.$companyOption.'</error>');

                    return \MultiFlexi\Cli\Command\MultiFlexiCommand::FAILURE;
                }
            }

            // Override company_id option for downstream logic
            $input->setOption('company_id', $companyId);
        }

        switch ($action) {
            case 'list':
                $rt = new RunTemplate();
                $query = $rt->listingQuery();
                $companyOption = $input->getOption('company');

                if ($companyOption) {
                    // Join with company table and filter by id or slug
                    $query->join('company ON company.id = runtemplate.company_id');

                    if (is_numeric($companyOption)) {
                        $query->where('company.id', (int) $companyOption);
                    } else {
                        $query->where('company.slug', $companyOption);
                    }
                }

                $rts = $query->fetchAll();

                if ($format === 'json') {
                    $output->writeln(json_encode($rts, \JSON_PRETTY_PRINT));
                } else {
                    foreach ($rts as $row) {
                        $output->writeln(implode(' | ', $row));
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'get':
                $id = $input->getOption('id');
                $name = $input->getOption('name');

                if (empty($id) && empty($name)) {
                    $output->writeln('<error>Missing --id or --name for runtemplate get</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                if (!empty($id)) {
                    $runtemplate = new RunTemplate((int) $id);
                } else {
                    // Lookup by name
                    $rt = new RunTemplate();
                    $row = $rt->listingQuery()->where('name', $name)->fetch();

                    if (!$row) {
                        $output->writeln('<error>RunTemplate not found by name</error>');

                        return MultiFlexiCommand::FAILURE;
                    }

                    $runtemplate = new RunTemplate((int) $row['id']);
                }

                $fields = $input->getOption('fields');

                if ($fields) {
                    $fieldsArray = explode(',', $fields);
                    $filteredData = array_filter(
                        $runtemplate->getData(),
                        static fn ($key) => \in_array($key, $fieldsArray, true),
                        \ARRAY_FILTER_USE_KEY,
                    );

                    if ($format === 'json') {
                        $output->writeln(json_encode($filteredData, \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($filteredData as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                } else {
                    if ($format === 'json') {
                        $output->writeln(json_encode($runtemplate->getData(), \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($runtemplate->getData() as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'create':
                $data = [];

                foreach (['name', 'app_id', 'company_id', 'interv', 'active'] as $field) {
                    $val = $input->getOption($field);

                    if ($val !== null) {
                        $data[$field] = $val;
                    }
                }

                // Set default interv if not provided
                if (empty($data['interv'])) {
                    $data['interv'] = 'n';
                }

                // If app_uuid is provided, resolve app_id by uuid
                $appUuid = $input->getOption('app_uuid');

                if ($appUuid !== null) {
                    // Try to resolve app_id from uuid
                    $pdo = (new \MultiFlexi\RunTemplate())->getFluentPDO()->getPdo();
                    $stmt = $pdo->prepare('SELECT id FROM apps WHERE uuid = :uuid');
                    $stmt->execute(['uuid' => $appUuid]);
                    $row = $stmt->fetch();

                    if ($row && isset($row['id'])) {
                        $data['app_id'] = $row['id'];
                    } else {
                        if ($format === 'json') {
                            $output->writeln(json_encode([
                                'status' => 'error',
                                'message' => 'Application with given UUID not found: '.$appUuid,
                                'uuid' => $appUuid,
                            ], \JSON_PRETTY_PRINT));
                        } else {
                            $output->writeln('<error>Application with given UUID not found: '.$appUuid.'</error>');
                        }

                        return MultiFlexiCommand::FAILURE;
                    }
                }

                if (empty($data['name']) || empty($data['app_id']) || empty($data['company_id'])) {
                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'status' => 'error',
                            'message' => 'Missing --name, --app_id or --company_id for runtemplate create',
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln('<error>Missing --name, --app_id or --company_id for runtemplate create</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $rt = new \MultiFlexi\RunTemplate();
                $rt->takeData($data);
                $rtId = $rt->saveToSQL();

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $full = (new \MultiFlexi\RunTemplate((int) $rtId))->getData();

                    if ($format === 'json') {
                        $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($full as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                } else {
                    $output->writeln(json_encode(['runtemplate_id' => $rtId, 'created' => true], \JSON_PRETTY_PRINT));
                }

                return MultiFlexiCommand::SUCCESS;
            case 'update':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for runtemplate update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $data = [];

                foreach (['name', 'app_id', 'company_id', 'interv', 'active'] as $field) {
                    $val = $input->getOption($field);

                    if ($val !== null) {
                        $data[$field] = $val;
                    }
                }

                // If app_uuid is provided, resolve app_id by uuid
                $appUuid = $input->getOption('app_uuid');

                if ($appUuid !== null) {
                    $pdo = (new \MultiFlexi\RunTemplate())->getFluentPDO()->getPdo();
                    $stmt = $pdo->prepare('SELECT id FROM apps WHERE uuid = :uuid');
                    $stmt->execute(['uuid' => $appUuid]);
                    $row = $stmt->fetch();

                    if ($row && isset($row['id'])) {
                        $data['app_id'] = $row['id'];
                    } else {
                        $output->writeln('<error>Application with given UUID not found</error>');

                        return MultiFlexiCommand::FAILURE;
                    }
                }

                $rt = new RunTemplate((int) $id);

                if (!empty($data)) {
                    $rt->updateToSQL($data, ['id' => $id]);
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
                    $output->writeln(json_encode(['runtemplate_id' => $id, 'updated' => true], \JSON_PRETTY_PRINT));
                }

                return MultiFlexiCommand::SUCCESS;
            case 'delete':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for runtemplate delete</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $rt = new \MultiFlexi\RunTemplate((int) $id);
                $rt->deleteFromSQL();

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln("RunTemplate deleted: ID={$id}");
                } else {
                    $output->writeln(json_encode(['runtemplate_id' => $id, 'deleted' => true], \JSON_PRETTY_PRINT));
                }

                return MultiFlexiCommand::SUCCESS;
            case 'schedule':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for runtemplate schedule</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $scheduleTime = $input->getOption('schedule_time') ?? 'now';
                $executor = $input->getOption('executor') ?? 'Native';
                $envOverrides = $input->getOption('env') ?? [];

                try {
                    $rt = new \MultiFlexi\RunTemplate(is_numeric($id) ? (int) $id : $id);

                    if (empty($rt->getMyKey())) {
                        $output->writeln('<error>RunTemplate not found</error>');

                        return MultiFlexiCommand::FAILURE;
                    }

                    if ((int) $rt->getDataValue('active') !== 1) {
                        $output->writeln('<error>RunTemplate is not active. Scheduling forbidden.</error>');

                        return MultiFlexiCommand::FAILURE;
                    }

                    $jobber = new Job();
                    // Prepare environment overrides as ConfigFields
                    $uploadEnv = new ConfigFields('Overrides');

                    foreach ($envOverrides as $item) {
                        if (str_contains($item, '=')) {
                            [$key, $value] = explode('=', $item, 2);
                            $uploadEnv->addField(new ConfigField($key, 'string', $key, '', '', $value));
                        }
                    }

                    $when = $scheduleTime;
                    $prepared = $jobber->prepareJob($rt->getMyKey(), $uploadEnv, new \DateTime($when), $executor);
                    $scheduleId = $jobber->scheduleJobRun(new \DateTime($when));
                    $output->writeln(json_encode([
                        'runtemplate_id' => $id,
                        'scheduled' => (new \DateTime($when))->format('Y-m-d H:i:s'),
                        'executor' => $executor,
                        'schedule_id' => $scheduleId,
                        'job_id' => $jobber->getMyKey(),
                    ], \JSON_PRETTY_PRINT));

                    return MultiFlexiCommand::SUCCESS;
                } catch (\Exception $e) {
                    $output->writeln('<error>Failed to schedule runtemplate: '.$e->getMessage().'</error>');

                    return MultiFlexiCommand::FAILURE;
                }

            default:
                $output->writeln("<error>Unknown action: {$action}</error>");

                return MultiFlexiCommand::FAILURE;
        }
    }
}

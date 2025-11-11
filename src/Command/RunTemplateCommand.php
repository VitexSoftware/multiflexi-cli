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

/**
 * Command for managing RunTemplates.
 *
 * Handles CRUD, scheduling, and configuration for runtemplates.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class RunTemplateCommand extends MultiFlexiCommand
{
    /**
     * The database field used to store config as JSON.
     */
    public const CONFIG_FIELD = 'config_json';
    protected static $defaultName = 'runtemplate';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    public function setRuntemplateConfig(int $runtemplateId, array $overrideEnv)
    {
        $rt = new RunTemplate((int) $id);

        if (!empty($overrideEnv)) {
            if ($rt->setEnvironment($overrideEnv)) {
                $configurator->addStatusMessage(_('Config fields Saved'), 'success');
                // Optionally run setup command if defined
                $setupCommand = $rt->getApplication()->getDataValue('setup');

                if (!empty($setupCommand)) {
                    $appEnvironment = $rt->getEnvironment()->getEnvArray();
                    $process = new \Symfony\Component\Process\Process(
                        explode(' ', $setupCommand),
                        null,
                        $appEnvironment,
                        null,
                        32767,
                    );
                    $result = $process->run();
                    $outputText = $process->getOutput();
                    $errorText = $process->getErrorOutput();

                    if ($result === 0) {
                        $configurator->addStatusMessage(_('Setup command executed successfully:'), 'success');

                        if ($outputText) {
                            $configurator->addStatusMessage($outputText, 'info');
                        }
                    } else {
                        $configurator->addStatusMessage(_('Setup command failed:'), 'error');

                        if ($errorText) {
                            $configurator->addStatusMessage($errorText, 'error');
                        }
                    }
                }
            } else {
                $configurator->addStatusMessage(_('Error saving Config fields'), 'error');
                $output->writeln('<error>Error saving Config fields</error>');

                return MultiFlexiCommand::FAILURE;
            }
        }
    }

    /**
     * Validate a crontab expression (basic 5-field check).
     */
    protected function isValidCronExpression(string $expression): bool
    {
        // Accepts 5 fields separated by spaces, each field can be *, number, range, list, or step
        $parts = preg_split('/\s+/', trim($expression));

        if (\count($parts) !== 5) {
            return false;
        }

        // Basic check for allowed characters in each field
        foreach ($parts as $field) {
            if (!preg_match('/^[\d\*,\/-]+$/', $field)) {
                return false;
            }
        }

        return true;
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
            ->addOption('cron', null, InputOption::VALUE_OPTIONAL, 'Crontab expression for scheduling')
            ->addOption('active', null, InputOption::VALUE_REQUIRED, 'Active')
            ->addOption('config', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Application config key=value (repeatable)')
            ->addOption('schedule_time', null, InputOption::VALUE_OPTIONAL, 'Schedule time for launch (Y-m-d H:i:s or "now")', 'now')
            ->addOption('executor', null, InputOption::VALUE_OPTIONAL, 'Executor to use for launch', 'Native')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of fields to display')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)');
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

        $overrideEnv = $this->parseConfigOptions($input);
        $overridedEnv = new ConfigFields('CommandlineOverride');

        foreach ($overrideEnvironment as $item) {
            if (str_contains($item, '=')) {
                [$key, $value] = explode('=', $item, 2);
                $overridedEnv->addField(new ConfigField($key, 'string', $key, '', '', $value));
            }
        }

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

                // Filter by app_uuid if provided
                $appUuid = $input->getOption('app_uuid');

                if ($appUuid) {
                    // Join with apps table and filter by uuid
                    $query->join('apps ON apps.id = runtemplate.app_id');
                    $query->where('apps.uuid', $appUuid);
                }

                // Handle order option
                $order = $input->getOption('order');

                if (!empty($order)) {
                    $orderBy = strtoupper($order) === 'D' ? 'DESC' : 'ASC';
                    $query = $query->orderBy('id '.$orderBy);
                }

                // Handle limit option
                $limit = $input->getOption('limit');

                if (!empty($limit) && is_numeric($limit)) {
                    $query = $query->limit((int) $limit);
                }

                // Handle offset option
                $offset = $input->getOption('offset');

                if (!empty($offset) && is_numeric($offset)) {
                    $query = $query->offset((int) $offset);
                }

                $rts = $query->fetchAll();

                // Handle fields option
                $fields = $input->getOption('fields');

                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $rts = array_map(static function ($rt) use ($fieldList) {
                        return array_intersect_key($rt, array_flip($fieldList));
                    }, $rts);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($rts, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln(self::outputTable($rts));
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
                        $output->writeln(json_encode(array_merge($runtemplate->getData(), $runtemplate->getEnvironment()->getEnvArray()), \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($runtemplate->getData() as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }

                        foreach ($runtemplate->getRuntemplateEnvironment()->getEnvArray() as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'create':
                $data = [];

                foreach (['name', 'app_id', 'company_id', 'interv', 'cron', 'active'] as $field) {
                    $val = $input->getOption($field);

                    if ($field === 'cron' && $val !== null) {
                        if (!$this->isValidCronExpression($val)) {
                            if ($format === 'json') {
                                $output->writeln(json_encode([
                                    'status' => 'error',
                                    'message' => 'Invalid crontab expression',
                                    'cron' => $val,
                                ], \JSON_PRETTY_PRINT));
                            } else {
                                $output->writeln('<error>Invalid crontab expression: '.$val.'</error>');
                            }

                            return MultiFlexiCommand::FAILURE;
                        }
                    }

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
                $rt->saveToSQL();
                $rtId = $this->getMyKey();  

                $this->setRuntemplateConfig($rtId, $overrideEnv);
                
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
                    if ($format === 'json') {
                        $output->writeln(json_encode(['runtemplate_id' => $rtId, 'created' => true], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln("RunTemplate created with ID: {$rtId}");
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'update':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for runtemplate update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $data = [];

                foreach (['name', 'app_id', 'company_id', 'interv', 'cron', 'active'] as $field) {
                    $val = $input->getOption($field);

                    if ($field === 'cron' && $val !== null) {
                        if (!$this->isValidCronExpression($val)) {
                            if ($format === 'json') {
                                $output->writeln(json_encode([
                                    'status' => 'error',
                                    'message' => 'Invalid crontab expression',
                                    'cron' => $val,
                                ], \JSON_PRETTY_PRINT));
                            } else {
                                $output->writeln('<error>Invalid crontab expression: '.$val.'</error>');
                            }

                            return MultiFlexiCommand::FAILURE;
                        }
                    }

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

                $this->setRuntemplateConfig();

                if (!empty($data)) {
                    try {
                        $rt->updateToSQL($data, ['id' => $id]);
                    } catch (\Exception $e) {
                        $output->writeln('<error>Failed to update runtemplate: '.$e->getMessage().'</error>');

                        return MultiFlexiCommand::FAILURE;
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
                    if ($format === 'json') {
                        $output->writeln(json_encode(['runtemplate_id' => $id, 'deleted' => true], \JSON_PRETTY_PRINT));
                    } else {
                        $output->writeln("RunTemplate deleted successfully (ID: {$id})");
                    }
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

                    $when = $scheduleTime;
                    $prepared = $jobber->prepareJob($rt->getMyKey(), $overridedEnv, new \DateTime($when), $executor);
                    $scheduleId = $jobber->scheduleJobRun(new \DateTime($when));

                    if ($format === 'json') {
                        $output->writeln(json_encode([
                            'runtemplate_id' => $id,
                            'scheduled' => (new \DateTime($when))->format('Y-m-d H:i:s'),
                            'executor' => $executor,
                            'schedule_id' => $scheduleId,
                            'job_id' => $jobber->getMyKey(),
                        ], \JSON_PRETTY_PRINT));
                    } else {
                        $scheduledTime = (new \DateTime($when))->format('Y-m-d H:i:s');
                        $output->writeln("RunTemplate {$id} scheduled for execution at {$scheduledTime}");
                        $output->writeln("Executor: {$executor}");
                        $output->writeln("Job ID: {$jobber->getMyKey()}");
                        $output->writeln("Schedule ID: {$scheduleId}");
                    }

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

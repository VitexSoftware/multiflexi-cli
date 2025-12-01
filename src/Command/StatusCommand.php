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

use Ease\Shared;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of Status.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class StatusCommand extends MultiFlexiCommand
{
    protected function configure(): void
    {
        $this
            ->setName('status')
            ->setDescription('Prints MultiFlexi Status')
            ->addOption('--format', '-f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->setHelp('This command prints overall MultiFlexi status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');

        $engine = new \MultiFlexi\Engine();
        $pdo = $engine->getPdo();

        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $dbFile = Shared::cfg('DB_DATABASE');
            $database = $driver.' '.$dbFile;

            if (is_file($dbFile)) {
                $stat = stat($dbFile);
                $owner = \function_exists('posix_getpwuid') ? (posix_getpwuid($stat['uid'])['name'] ?? $stat['uid']) : $stat['uid'];
                $group = \function_exists('posix_getgrgid') ? (posix_getgrgid($stat['gid'])['name'] ?? $stat['gid']) : $stat['gid'];
                $mode = substr(sprintf('%o', $stat['mode']), -4);
                $database .= sprintf(' (owner: %s, group: %s, mode: %s)', $owner, $group, $mode);
            }
        } else {
            $database = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME).' '.
                    $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS).' '.
                    $pdo->getAttribute(\PDO::ATTR_SERVER_INFO).' '.
                    $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }

        $databaseVersion = $engine->getFluentPDO()->from('phinxlog')->orderBy('version DESC')->limit(1)->fetch();

        // Check encryption status
        $encryptionStatus = self::getEncryptionStatus($engine);

        // Check OpenTelemetry status
        $otelStatus = self::getOpenTelemetryStatus();

        // Check Zabbix status
        $zabbixStatus = self::getZabbixStatus();

        // Get job statistics
        $jobStats = self::getJobStatistics($engine);

        $status = [
            'version-cli' => Shared::appVersion(),
            'db-migration' => $databaseVersion['migration_name'].' ('.$databaseVersion['version'].')',
            'user' => Shared::user()->getUserLogin(),
            'php' => \PHP_VERSION,
            'os' => \PHP_OS,
            'memory' => memory_get_usage(),
            'companies' => $engine->getFluentPDO()->from('company')->count(),
            'apps' => $engine->getFluentPDO()->from('apps')->count(),
            'runtemplates' => $engine->getFluentPDO()->from('runtemplate')->count(),
            'topics' => $engine->getFluentPDO()->from('topic')->count(),
            'credentials' => $engine->getFluentPDO()->from('credentials')->count(),
            'credential_types' => $engine->getFluentPDO()->from('credential_type')->count(),
            'jobs' => sprintf(
                'total: %s, monthly: %s, weekly: %s, daily: %s, hourly: %s, minute avg: %s',
                $jobStats['total'],
                $jobStats['last_month'],
                $jobStats['last_week'],
                $jobStats['last_day'],
                $jobStats['last_hour'],
                $jobStats['per_minute_avg'],
            ),
            'database' => $database,
            'encryption' => $encryptionStatus,
            'zabbix' => $zabbixStatus,
            'telemetry' => $otelStatus,
            'executor' => \MultiFlexi\Runner::getServiceStatus('multiflexi-executor.service'),
            'scheduler' => \MultiFlexi\Runner::getServiceStatus('multiflexi-scheduler.service'),
            'timestamp' => date('c'),
        ];

        if ($format === 'json') {
            $output->writeln(json_encode($status, \JSON_PRETTY_PRINT));
        } else {
            // Print as a vertical table: each row is a key-value pair

            foreach ($status as $key => $value) {
                $statusTable[] = [$key, (string) $value];
            }

            $output->writeln(self::outputTable($statusTable, 200, ['Key', 'Value']));
        }

        return MultiFlexiCommand::SUCCESS;
    }

    /**
     * Check encryption system status.
     *
     * @return string Status: 'disabled', 'active', 'broken', or 'unknown'
     */
    private static function getEncryptionStatus(\MultiFlexi\Engine $engine): string
    {
        // Check if DATA_ENCRYPTION_ENABLED is set
        $encryptionEnabled = Shared::cfg('DATA_ENCRYPTION_ENABLED', true);

        if (!$encryptionEnabled) {
            return 'disabled';
        }

        // Check if ENCRYPTION_MASTER_KEY is configured
        $masterKey = getenv('ENCRYPTION_MASTER_KEY');

        if (!$masterKey) {
            $masterKey = getenv('MULTIFLEXI_MASTER_KEY');
        }

        if (!$masterKey) {
            $masterKey = Shared::cfg('ENCRYPTION_MASTER_KEY');
        }

        if (!$masterKey) {
            return 'broken (no master key)';
        }

        // Check if encryption_keys table exists and has keys
        try {
            $pdo = $engine->getPdo();
            $stmt = $pdo->query('SELECT COUNT(*) FROM encryption_keys WHERE is_active = TRUE');
            $activeKeyCount = $stmt->fetchColumn();

            if ($activeKeyCount > 0) {
                return 'active ('.$activeKeyCount.' keys)';
            }

            return 'broken (no active keys)';
        } catch (\PDOException $e) {
            // Table might not exist
            if (str_contains($e->getMessage(), 'no such table')
                || str_contains($e->getMessage(), "doesn't exist")) {
                return 'broken (table missing)';
            }

            return 'unknown (error: '.$e->getMessage().')';
        }
    }

    /**
     * Check OpenTelemetry configuration status.
     *
     * @return string Status: 'disabled' or configured endpoint
     */
    private static function getOpenTelemetryStatus(): string
    {
        // Check if OTEL_ENABLED is set
        $otelEnabled = Shared::cfg('OTEL_ENABLED', false);

        if (!$otelEnabled || $otelEnabled === 'false' || $otelEnabled === '0') {
            return 'disabled';
        }

        // Get the configured endpoint
        $endpoint = Shared::cfg('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318');
        $protocol = Shared::cfg('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/json');
        $serviceName = Shared::cfg('OTEL_SERVICE_NAME', 'multiflexi');

        // Check if OTel SDK is available
        if (!class_exists('\OpenTelemetry\SDK\Metrics\MeterProvider')) {
            return 'enabled (SDK not installed)';
        }

        return sprintf('enabled (%s, %s, %s)', $serviceName, $endpoint, $protocol);
    }

    /**
     * Check Zabbix configuration status.
     *
     * @return string Status: 'disabled' or monitored hostname => zabbix server
     */
    private static function getZabbixStatus(): string
    {
        // Check if ZABBIX_SERVER is configured
        $zabbixServer = Shared::cfg('ZABBIX_SERVER');

        if (!$zabbixServer || empty($zabbixServer)) {
            return 'disabled';
        }

        // Get the monitored machine hostname
        $monitoredHost = Shared::cfg('ZABBIX_HOST');

        // If ZABBIX_HOST is not set, use system hostname
        if (!$monitoredHost || empty($monitoredHost)) {
            $monitoredHost = gethostname() ?: 'localhost';
        }

        return sprintf('%s => %s', $monitoredHost, $zabbixServer);
    }

    /**
     * Get job statistics from database.
     *
     * @param \MultiFlexi\Engine $engine Database engine
     *
     * @return array Job statistics
     */
    private static function getJobStatistics(\MultiFlexi\Engine $engine): array
    {
        try {
            $pdo = $engine->getPdo();
            $now = new \DateTime();

            // First, try to determine what timestamp column exists
            $timestampColumn = null;
            $columns = ['created', 'created_at', 'timestamp', 'begin', 'started', 'launched'];

            foreach ($columns as $col) {
                try {
                    $engine->getFluentPDO()->from('job')->where($col.' IS NOT NULL')->limit(1)->fetch();
                    $timestampColumn = $col;

                    break;
                } catch (\Exception $e) {
                    // Column doesn't exist, try next one
                    continue;
                }
            }

            // Total jobs
            $totalJobs = $engine->getFluentPDO()->from('job')->count();

            if ($timestampColumn === null) {
                // No timestamp column found, return basic stats
                return [
                    'total' => $totalJobs,
                    'last_month' => 'N/A',
                    'last_week' => 'N/A',
                    'last_day' => 'N/A',
                    'last_hour' => 'N/A',
                    'per_minute_avg' => 'N/A',
                ];
            }

            // Jobs last month
            $lastMonth = clone $now;
            $lastMonth->modify('-1 month');
            $jobsLastMonth = $engine->getFluentPDO()->from('job')
                ->where($timestampColumn.' >= ?', $lastMonth->format('Y-m-d H:i:s'))
                ->count();

            // Jobs last week
            $lastWeek = clone $now;
            $lastWeek->modify('-1 week');
            $jobsLastWeek = $engine->getFluentPDO()->from('job')
                ->where($timestampColumn.' >= ?', $lastWeek->format('Y-m-d H:i:s'))
                ->count();

            // Jobs last day
            $lastDay = clone $now;
            $lastDay->modify('-1 day');
            $jobsLastDay = $engine->getFluentPDO()->from('job')
                ->where($timestampColumn.' >= ?', $lastDay->format('Y-m-d H:i:s'))
                ->count();

            // Jobs last hour
            $lastHour = clone $now;
            $lastHour->modify('-1 hour');
            $jobsLastHour = $engine->getFluentPDO()->from('job')
                ->where($timestampColumn.' >= ?', $lastHour->format('Y-m-d H:i:s'))
                ->count();

            // Average jobs per minute (based on last day)
            $jobsPerMinuteAvg = 0;

            if ($jobsLastDay > 0) {
                $jobsPerMinuteAvg = round($jobsLastDay / (24 * 60), 2);
            }

            return [
                'total' => $totalJobs,
                'last_month' => $jobsLastMonth,
                'last_week' => $jobsLastWeek,
                'last_day' => $jobsLastDay,
                'last_hour' => $jobsLastHour,
                'per_minute_avg' => $jobsPerMinuteAvg,
            ];
        } catch (\Exception $e) {
            // If any error occurs, return basic total count and N/A for time-based stats
            try {
                $totalJobs = $engine->getFluentPDO()->from('job')->count();
            } catch (\Exception $e) {
                $totalJobs = 'N/A';
            }

            return [
                'total' => $totalJobs,
                'last_month' => 'N/A',
                'last_week' => 'N/A',
                'last_day' => 'N/A',
                'last_hour' => 'N/A',
                'per_minute_avg' => 'N/A',
            ];
        }
    }
}

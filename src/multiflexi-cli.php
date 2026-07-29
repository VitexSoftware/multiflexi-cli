#!/usr/bin/env php
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

namespace MultiFlexi\Cli;

require_once __DIR__.'/../vendor/autoload.php';

use Ease\Anonym;
use Ease\Shared;
use MultiFlexi\Cli\Command\Application\CreateCommand as ApplicationCreateCommand;
use MultiFlexi\Cli\Command\Application\DeleteCommand as ApplicationDeleteCommand;
use MultiFlexi\Cli\Command\Application\ExportJsonCommand as ApplicationExportJsonCommand;
use MultiFlexi\Cli\Command\Application\GetCommand as ApplicationGetCommand;
use MultiFlexi\Cli\Command\Application\ImportJsonCommand as ApplicationImportJsonCommand;
use MultiFlexi\Cli\Command\Application\ListCommand as ApplicationListCommand;
use MultiFlexi\Cli\Command\Application\RemoveJsonCommand as ApplicationRemoveJsonCommand;
use MultiFlexi\Cli\Command\Application\ShowConfigCommand as ApplicationShowConfigCommand;
use MultiFlexi\Cli\Command\Application\UpdateCommand as ApplicationUpdateCommand;
use MultiFlexi\Cli\Command\Application\ValidateJsonCommand as ApplicationValidateJsonCommand;
use MultiFlexi\Cli\Command\Artifact\GetCommand as ArtifactGetCommand;
use MultiFlexi\Cli\Command\Artifact\ListCommand as ArtifactListCommand;
use MultiFlexi\Cli\Command\Artifact\SaveCommand as ArtifactSaveCommand;
use MultiFlexi\Cli\Command\Company\CreateCommand as CompanyCreateCommand;
use MultiFlexi\Cli\Command\Company\GetCommand as CompanyGetCommand;
use MultiFlexi\Cli\Command\Company\ListCommand as CompanyListCommand;
use MultiFlexi\Cli\Command\Company\RemoveCommand as CompanyRemoveCommand;
use MultiFlexi\Cli\Command\Company\UpdateCommand as CompanyUpdateCommand;
use MultiFlexi\Cli\Command\CompanyApp\AssignCommand as CompanyAppAssignCommand;
use MultiFlexi\Cli\Command\CompanyApp\ListCommand as CompanyAppListCommand;
use MultiFlexi\Cli\Command\CompanyApp\UnassignCommand as CompanyAppUnassignCommand;
use MultiFlexi\Cli\Command\Credential\CreateCommand as CredentialCreateCommand;
use MultiFlexi\Cli\Command\Credential\GetCommand as CredentialGetCommand;
use MultiFlexi\Cli\Command\Credential\ListCommand as CredentialListCommand;
use MultiFlexi\Cli\Command\Credential\RemoveCommand as CredentialRemoveCommand;
use MultiFlexi\Cli\Command\Credential\UpdateCommand as CredentialUpdateCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\CreateCommand as CredentialPrototypeCreateCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\DeleteCommand as CredentialPrototypeDeleteCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\ExportJsonCommand as CredentialPrototypeExportJsonCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\GetCommand as CredentialPrototypeGetCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\ImportJsonCommand as CredentialPrototypeImportJsonCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\ListCommand as CredentialPrototypeListCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\SyncCommand as CredentialPrototypeSyncCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\UpdateCommand as CredentialPrototypeUpdateCommand;
use MultiFlexi\Cli\Command\CredentialPrototype\ValidateJsonCommand as CredentialPrototypeValidateJsonCommand;
use MultiFlexi\Cli\Command\CredentialType\CreateCommand as CredentialTypeCreateCommand;
use MultiFlexi\Cli\Command\CredentialType\DeleteCommand as CredentialTypeDeleteCommand;
use MultiFlexi\Cli\Command\CredentialType\GetCommand as CredentialTypeGetCommand;
use MultiFlexi\Cli\Command\CredentialType\ImportJsonCommand as CredentialTypeImportJsonCommand;
use MultiFlexi\Cli\Command\CredentialType\ListCommand as CredentialTypeListCommand;
use MultiFlexi\Cli\Command\CredentialType\UpdateCommand as CredentialTypeUpdateCommand;
use MultiFlexi\Cli\Command\CredentialType\ValidateJsonCommand as CredentialTypeValidateJsonCommand;
use MultiFlexi\Cli\Command\DescribeCommand;
use MultiFlexi\Cli\Command\Encryption\EncryptExistingCommand;
use MultiFlexi\Cli\Command\Encryption\InitCommand as EncryptionInitCommand;
use MultiFlexi\Cli\Command\Encryption\StatusCommand as EncryptionStatusCommand;
use MultiFlexi\Cli\Command\EventRule\CreateCommand as EventRuleCreateCommand;
use MultiFlexi\Cli\Command\EventRule\GetCommand as EventRuleGetCommand;
use MultiFlexi\Cli\Command\EventRule\ListCommand as EventRuleListCommand;
use MultiFlexi\Cli\Command\EventRule\RemoveCommand as EventRuleRemoveCommand;
use MultiFlexi\Cli\Command\EventRule\UpdateCommand as EventRuleUpdateCommand;
use MultiFlexi\Cli\Command\EventSource\CreateCommand as EventSourceCreateCommand;
use MultiFlexi\Cli\Command\EventSource\GetCommand as EventSourceGetCommand;
use MultiFlexi\Cli\Command\EventSource\ListCommand as EventSourceListCommand;
use MultiFlexi\Cli\Command\EventSource\RemoveCommand as EventSourceRemoveCommand;
use MultiFlexi\Cli\Command\EventSource\TestCommand as EventSourceTestCommand;
use MultiFlexi\Cli\Command\EventSource\UpdateCommand as EventSourceUpdateCommand;
use MultiFlexi\Cli\Command\Job\CreateCommand as JobCreateCommand;
use MultiFlexi\Cli\Command\Job\DeleteCommand as JobDeleteCommand;
use MultiFlexi\Cli\Command\Job\GetCommand as JobGetCommand;
use MultiFlexi\Cli\Command\Job\ListCommand as JobListCommand;
use MultiFlexi\Cli\Command\Job\StatusCommand as JobStatusCommand;
use MultiFlexi\Cli\Command\Job\UpdateCommand as JobUpdateCommand;
use MultiFlexi\Cli\Command\PruneCommand;
use MultiFlexi\Cli\Command\Queue\FixCommand as QueueFixCommand;
use MultiFlexi\Cli\Command\Queue\ListCommand as QueueListCommand;
use MultiFlexi\Cli\Command\Queue\OverviewCommand as QueueOverviewCommand;
use MultiFlexi\Cli\Command\Queue\TruncateCommand as QueueTruncateCommand;
use MultiFlexi\Cli\Command\RunTemplate\AssignCredentialCommand as RunTemplateAssignCredentialCommand;
use MultiFlexi\Cli\Command\RunTemplate\CreateCommand as RunTemplateCreateCommand;
use MultiFlexi\Cli\Command\RunTemplate\DeleteCommand as RunTemplateDeleteCommand;
use MultiFlexi\Cli\Command\RunTemplate\GetCommand as RunTemplateGetCommand;
use MultiFlexi\Cli\Command\RunTemplate\ListCommand as RunTemplateListCommand;
use MultiFlexi\Cli\Command\RunTemplate\ListCredentialsCommand as RunTemplateListCredentialsCommand;
use MultiFlexi\Cli\Command\RunTemplate\ScheduleCommand as RunTemplateScheduleCommand;
use MultiFlexi\Cli\Command\RunTemplate\StaleCommand as RunTemplateStaleCommand;
use MultiFlexi\Cli\Command\RunTemplate\UnassignCredentialCommand as RunTemplateUnassignCredentialCommand;
use MultiFlexi\Cli\Command\RunTemplate\UpdateCommand as RunTemplateUpdateCommand;
use MultiFlexi\Cli\Command\StatusCommand;
use MultiFlexi\Cli\Command\Task\GetCommand as TaskGetCommand;
use MultiFlexi\Cli\Command\Task\ListCommand as TaskListCommand;
use MultiFlexi\Cli\Command\Task\StatusCommand as TaskStatusCommand;
use MultiFlexi\Cli\Command\TelemetryTestCommand;
use MultiFlexi\Cli\Command\Token\CreateCommand as TokenCreateCommand;
use MultiFlexi\Cli\Command\Token\DeleteCommand as TokenDeleteCommand;
use MultiFlexi\Cli\Command\Token\GenerateCommand as TokenGenerateCommand;
use MultiFlexi\Cli\Command\Token\GetCommand as TokenGetCommand;
use MultiFlexi\Cli\Command\Token\ListCommand as TokenListCommand;
use MultiFlexi\Cli\Command\Token\UpdateCommand as TokenUpdateCommand;
use MultiFlexi\Cli\Command\User\CreateCommand as UserCreateCommand;
use MultiFlexi\Cli\Command\User\DeleteCommand as UserDeleteCommand;
use MultiFlexi\Cli\Command\User\GetCommand as UserGetCommand;
use MultiFlexi\Cli\Command\User\ListCommand as UserListCommand;
use MultiFlexi\Cli\Command\User\UpdateCommand as UserUpdateCommand;
use MultiFlexi\Cli\Command\UserCompany\AssignCommand as UserCompanyAssignCommand;
use MultiFlexi\Cli\Command\UserCompany\UnassignCommand as UserCompanyUnassignCommand;
use MultiFlexi\Cli\Command\UserErasure\ApproveCommand as UserErasureApproveCommand;
use MultiFlexi\Cli\Command\UserErasure\AuditCommand as UserErasureAuditCommand;
use MultiFlexi\Cli\Command\UserErasure\CleanupCommand as UserErasureCleanupCommand;
use MultiFlexi\Cli\Command\UserErasure\CreateCommand as UserErasureCreateCommand;
use MultiFlexi\Cli\Command\UserErasure\ListCommand as UserErasureListCommand;
use MultiFlexi\Cli\Command\UserErasure\ProcessCommand as UserErasureProcessCommand;
use MultiFlexi\Cli\Command\UserErasure\RejectCommand as UserErasureRejectCommand;
use MultiFlexi\Cli\Command\UserRole\SetCommand as UserRoleSetCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\CompleteCommand;

$globalOptions = getopt('e::', ['environment::']);

$defaultEnv = \Phar::running() ? '/etc/multiflexi/multiflexi.env' : __DIR__.'/../.env';

Shared::init(
    ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
    \array_key_exists('environment', $globalOptions) ? $globalOptions['environment'] : (\array_key_exists('e', $globalOptions) ? $globalOptions['e'] : $defaultEnv),
);

date_default_timezone_set(\MultiFlexi\DateTimeHelper::getConfiguredTimezoneString());

if (\Ease\Shared::cfg('EASE_LOGGER', false) === false) {
    $loggers = ['syslog', '\MultiFlexi\LogToSQL'];

    if (Shared::cfg('ZABBIX_SERVER') && Shared::cfg('ZABBIX_HOST') && class_exists('\MultiFlexi\LogToZabbix')) {
        $loggers[] = '\MultiFlexi\LogToZabbix';
    }

    if (Shared::cfg('APP_DEBUG') === 'true') {
        $loggers[] = 'console';
    }

    \define('EASE_LOGGER', implode('|', $loggers));
}

\define('APP_NAME', 'MultiFlexiCLI');

new \MultiFlexi\Defaults();

Shared::user((Shared::cfg('DB_CONNECTION') === 'dummy') ? new Anonym() : new \MultiFlexi\UnixUser());

$application = new Application(Shared::appName(), Shared::appVersion());

// Application
ConsoleCompat::addCommand($application, new ApplicationListCommand());
ConsoleCompat::addCommand($application, new ApplicationGetCommand());
ConsoleCompat::addCommand($application, new ApplicationCreateCommand());
ConsoleCompat::addCommand($application, new ApplicationUpdateCommand());
ConsoleCompat::addCommand($application, new ApplicationDeleteCommand());
ConsoleCompat::addCommand($application, new ApplicationImportJsonCommand());
ConsoleCompat::addCommand($application, new ApplicationExportJsonCommand());
ConsoleCompat::addCommand($application, new ApplicationRemoveJsonCommand());
ConsoleCompat::addCommand($application, new ApplicationValidateJsonCommand());
ConsoleCompat::addCommand($application, new ApplicationShowConfigCommand());

// Artifact
ConsoleCompat::addCommand($application, new ArtifactListCommand());
ConsoleCompat::addCommand($application, new ArtifactGetCommand());
ConsoleCompat::addCommand($application, new ArtifactSaveCommand());

// Company
ConsoleCompat::addCommand($application, new CompanyListCommand());
ConsoleCompat::addCommand($application, new CompanyGetCommand());
ConsoleCompat::addCommand($application, new CompanyCreateCommand());
ConsoleCompat::addCommand($application, new CompanyUpdateCommand());
ConsoleCompat::addCommand($application, new CompanyRemoveCommand());

// CompanyApp
ConsoleCompat::addCommand($application, new CompanyAppListCommand());
ConsoleCompat::addCommand($application, new CompanyAppAssignCommand());
ConsoleCompat::addCommand($application, new CompanyAppUnassignCommand());

// Credential
ConsoleCompat::addCommand($application, new CredentialListCommand());
ConsoleCompat::addCommand($application, new CredentialGetCommand());
ConsoleCompat::addCommand($application, new CredentialCreateCommand());
ConsoleCompat::addCommand($application, new CredentialUpdateCommand());
ConsoleCompat::addCommand($application, new CredentialRemoveCommand());

// Credential Prototype
ConsoleCompat::addCommand($application, new CredentialPrototypeListCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeGetCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeCreateCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeUpdateCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeDeleteCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeImportJsonCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeExportJsonCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeValidateJsonCommand());
ConsoleCompat::addCommand($application, new CredentialPrototypeSyncCommand());

// Credential Type
ConsoleCompat::addCommand($application, new CredentialTypeListCommand());
ConsoleCompat::addCommand($application, new CredentialTypeGetCommand());
ConsoleCompat::addCommand($application, new CredentialTypeCreateCommand());
ConsoleCompat::addCommand($application, new CredentialTypeUpdateCommand());
ConsoleCompat::addCommand($application, new CredentialTypeDeleteCommand());
ConsoleCompat::addCommand($application, new CredentialTypeImportJsonCommand());
ConsoleCompat::addCommand($application, new CredentialTypeValidateJsonCommand());

// Encryption
ConsoleCompat::addCommand($application, new EncryptionStatusCommand());
ConsoleCompat::addCommand($application, new EncryptionInitCommand());
ConsoleCompat::addCommand($application, new EncryptExistingCommand());

// Event Rule
ConsoleCompat::addCommand($application, new EventRuleListCommand());
ConsoleCompat::addCommand($application, new EventRuleGetCommand());
ConsoleCompat::addCommand($application, new EventRuleCreateCommand());
ConsoleCompat::addCommand($application, new EventRuleUpdateCommand());
ConsoleCompat::addCommand($application, new EventRuleRemoveCommand());

// Event Source
ConsoleCompat::addCommand($application, new EventSourceListCommand());
ConsoleCompat::addCommand($application, new EventSourceGetCommand());
ConsoleCompat::addCommand($application, new EventSourceCreateCommand());
ConsoleCompat::addCommand($application, new EventSourceUpdateCommand());
ConsoleCompat::addCommand($application, new EventSourceRemoveCommand());
ConsoleCompat::addCommand($application, new EventSourceTestCommand());

// Job
ConsoleCompat::addCommand($application, new JobStatusCommand());
ConsoleCompat::addCommand($application, new JobListCommand());
ConsoleCompat::addCommand($application, new JobGetCommand());
ConsoleCompat::addCommand($application, new JobCreateCommand());
ConsoleCompat::addCommand($application, new JobUpdateCommand());
ConsoleCompat::addCommand($application, new JobDeleteCommand());

// Queue
ConsoleCompat::addCommand($application, new QueueOverviewCommand());
ConsoleCompat::addCommand($application, new QueueListCommand());
ConsoleCompat::addCommand($application, new QueueTruncateCommand());
ConsoleCompat::addCommand($application, new QueueFixCommand());

// Run Template
ConsoleCompat::addCommand($application, new RunTemplateListCommand());
ConsoleCompat::addCommand($application, new RunTemplateGetCommand());
ConsoleCompat::addCommand($application, new RunTemplateCreateCommand());
ConsoleCompat::addCommand($application, new RunTemplateUpdateCommand());
ConsoleCompat::addCommand($application, new RunTemplateDeleteCommand());
ConsoleCompat::addCommand($application, new RunTemplateScheduleCommand());
ConsoleCompat::addCommand($application, new RunTemplateStaleCommand());
ConsoleCompat::addCommand($application, new RunTemplateAssignCredentialCommand());
ConsoleCompat::addCommand($application, new RunTemplateUnassignCredentialCommand());
ConsoleCompat::addCommand($application, new RunTemplateListCredentialsCommand());

// Task
ConsoleCompat::addCommand($application, new TaskStatusCommand());
ConsoleCompat::addCommand($application, new TaskListCommand());
ConsoleCompat::addCommand($application, new TaskGetCommand());

// Token
ConsoleCompat::addCommand($application, new TokenListCommand());
ConsoleCompat::addCommand($application, new TokenGetCommand());
ConsoleCompat::addCommand($application, new TokenCreateCommand());
ConsoleCompat::addCommand($application, new TokenGenerateCommand());
ConsoleCompat::addCommand($application, new TokenUpdateCommand());
ConsoleCompat::addCommand($application, new TokenDeleteCommand());

// User
ConsoleCompat::addCommand($application, new UserListCommand());
ConsoleCompat::addCommand($application, new UserGetCommand());
ConsoleCompat::addCommand($application, new UserCreateCommand());
ConsoleCompat::addCommand($application, new UserUpdateCommand());
ConsoleCompat::addCommand($application, new UserDeleteCommand());

// User Company Assignment
ConsoleCompat::addCommand($application, new UserCompanyAssignCommand());
ConsoleCompat::addCommand($application, new UserCompanyUnassignCommand());

// User RBAC Roles
ConsoleCompat::addCommand($application, new UserRoleSetCommand());

// User Erasure (GDPR)
ConsoleCompat::addCommand($application, new UserErasureListCommand());
ConsoleCompat::addCommand($application, new UserErasureCreateCommand());
ConsoleCompat::addCommand($application, new UserErasureApproveCommand());
ConsoleCompat::addCommand($application, new UserErasureRejectCommand());
ConsoleCompat::addCommand($application, new UserErasureProcessCommand());
ConsoleCompat::addCommand($application, new UserErasureAuditCommand());
ConsoleCompat::addCommand($application, new UserErasureCleanupCommand());

// Standalone
ConsoleCompat::addCommand($application, new DescribeCommand());
ConsoleCompat::addCommand($application, new StatusCommand());
ConsoleCompat::addCommand($application, new PruneCommand());
ConsoleCompat::addCommand($application, new TelemetryTestCommand());
ConsoleCompat::addCommand($application, new CompleteCommand());

$application->run();

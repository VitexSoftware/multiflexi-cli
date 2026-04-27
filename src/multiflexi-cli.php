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
use MultiFlexi\Cli\Command\RunTemplate\CreateCommand as RunTemplateCreateCommand;
use MultiFlexi\Cli\Command\RunTemplate\DeleteCommand as RunTemplateDeleteCommand;
use MultiFlexi\Cli\Command\RunTemplate\GetCommand as RunTemplateGetCommand;
use MultiFlexi\Cli\Command\RunTemplate\ListCommand as RunTemplateListCommand;
use MultiFlexi\Cli\Command\RunTemplate\ScheduleCommand as RunTemplateScheduleCommand;
use MultiFlexi\Cli\Command\RunTemplate\UpdateCommand as RunTemplateUpdateCommand;
use MultiFlexi\Cli\Command\StatusCommand;
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
use MultiFlexi\Cli\Command\UserErasure\ApproveCommand as UserErasureApproveCommand;
use MultiFlexi\Cli\Command\UserErasure\AuditCommand as UserErasureAuditCommand;
use MultiFlexi\Cli\Command\UserErasure\CleanupCommand as UserErasureCleanupCommand;
use MultiFlexi\Cli\Command\UserErasure\CreateCommand as UserErasureCreateCommand;
use MultiFlexi\Cli\Command\UserErasure\ListCommand as UserErasureListCommand;
use MultiFlexi\Cli\Command\UserErasure\ProcessCommand as UserErasureProcessCommand;
use MultiFlexi\Cli\Command\UserErasure\RejectCommand as UserErasureRejectCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\CompleteCommand;

$globalOptions = getopt('e::', ['environment::']);

Shared::init(
    ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
    \array_key_exists('environment', $globalOptions) ? $globalOptions['environment'] : (\array_key_exists('e', $globalOptions) ? $globalOptions['e'] : __DIR__.'/../.env'),
);

$loggers = ['syslog', '\MultiFlexi\LogToSQL'];

if (Shared::cfg('ZABBIX_SERVER') && Shared::cfg('ZABBIX_HOST') && class_exists('\MultiFlexi\LogToZabbix')) {
    $loggers[] = '\MultiFlexi\LogToZabbix';
}

if (Shared::cfg('APP_DEBUG') === 'true') {
    $loggers[] = 'console';
}

\define('EASE_LOGGER', implode('|', $loggers));
\define('APP_NAME', 'MultiFlexiCLI');

new \MultiFlexi\Defaults();

Shared::user((Shared::cfg('DB_CONNECTION') === 'dummy') ? new Anonym() : new \MultiFlexi\UnixUser());

$application = new Application(Shared::appName(), Shared::appVersion());

// Application
$application->add(new ApplicationListCommand());
$application->add(new ApplicationGetCommand());
$application->add(new ApplicationCreateCommand());
$application->add(new ApplicationUpdateCommand());
$application->add(new ApplicationDeleteCommand());
$application->add(new ApplicationImportJsonCommand());
$application->add(new ApplicationExportJsonCommand());
$application->add(new ApplicationRemoveJsonCommand());
$application->add(new ApplicationValidateJsonCommand());
$application->add(new ApplicationShowConfigCommand());

// Artifact
$application->add(new ArtifactListCommand());
$application->add(new ArtifactGetCommand());
$application->add(new ArtifactSaveCommand());

// Company
$application->add(new CompanyListCommand());
$application->add(new CompanyGetCommand());
$application->add(new CompanyCreateCommand());
$application->add(new CompanyUpdateCommand());
$application->add(new CompanyRemoveCommand());

// CompanyApp
$application->add(new CompanyAppListCommand());
$application->add(new CompanyAppAssignCommand());
$application->add(new CompanyAppUnassignCommand());

// Credential
$application->add(new CredentialListCommand());
$application->add(new CredentialGetCommand());
$application->add(new CredentialCreateCommand());
$application->add(new CredentialUpdateCommand());
$application->add(new CredentialRemoveCommand());

// Credential Prototype
$application->add(new CredentialPrototypeListCommand());
$application->add(new CredentialPrototypeGetCommand());
$application->add(new CredentialPrototypeCreateCommand());
$application->add(new CredentialPrototypeUpdateCommand());
$application->add(new CredentialPrototypeDeleteCommand());
$application->add(new CredentialPrototypeImportJsonCommand());
$application->add(new CredentialPrototypeExportJsonCommand());
$application->add(new CredentialPrototypeValidateJsonCommand());
$application->add(new CredentialPrototypeSyncCommand());

// Credential Type
$application->add(new CredentialTypeListCommand());
$application->add(new CredentialTypeGetCommand());
$application->add(new CredentialTypeCreateCommand());
$application->add(new CredentialTypeUpdateCommand());
$application->add(new CredentialTypeDeleteCommand());
$application->add(new CredentialTypeImportJsonCommand());
$application->add(new CredentialTypeValidateJsonCommand());

// Encryption
$application->add(new EncryptionStatusCommand());
$application->add(new EncryptionInitCommand());

// Event Rule
$application->add(new EventRuleListCommand());
$application->add(new EventRuleGetCommand());
$application->add(new EventRuleCreateCommand());
$application->add(new EventRuleUpdateCommand());
$application->add(new EventRuleRemoveCommand());

// Event Source
$application->add(new EventSourceListCommand());
$application->add(new EventSourceGetCommand());
$application->add(new EventSourceCreateCommand());
$application->add(new EventSourceUpdateCommand());
$application->add(new EventSourceRemoveCommand());
$application->add(new EventSourceTestCommand());

// Job
$application->add(new JobStatusCommand());
$application->add(new JobListCommand());
$application->add(new JobGetCommand());
$application->add(new JobCreateCommand());
$application->add(new JobUpdateCommand());
$application->add(new JobDeleteCommand());

// Queue
$application->add(new QueueOverviewCommand());
$application->add(new QueueListCommand());
$application->add(new QueueTruncateCommand());
$application->add(new QueueFixCommand());

// Run Template
$application->add(new RunTemplateListCommand());
$application->add(new RunTemplateGetCommand());
$application->add(new RunTemplateCreateCommand());
$application->add(new RunTemplateUpdateCommand());
$application->add(new RunTemplateDeleteCommand());
$application->add(new RunTemplateScheduleCommand());

// Token
$application->add(new TokenListCommand());
$application->add(new TokenGetCommand());
$application->add(new TokenCreateCommand());
$application->add(new TokenGenerateCommand());
$application->add(new TokenUpdateCommand());
$application->add(new TokenDeleteCommand());

// User
$application->add(new UserListCommand());
$application->add(new UserGetCommand());
$application->add(new UserCreateCommand());
$application->add(new UserUpdateCommand());
$application->add(new UserDeleteCommand());

// User Erasure (GDPR)
$application->add(new UserErasureListCommand());
$application->add(new UserErasureCreateCommand());
$application->add(new UserErasureApproveCommand());
$application->add(new UserErasureRejectCommand());
$application->add(new UserErasureProcessCommand());
$application->add(new UserErasureAuditCommand());
$application->add(new UserErasureCleanupCommand());

// Standalone
$application->add(new DescribeCommand());
$application->add(new StatusCommand());
$application->add(new PruneCommand());
$application->add(new TelemetryTestCommand());
$application->add(new CompleteCommand());

$application->run();

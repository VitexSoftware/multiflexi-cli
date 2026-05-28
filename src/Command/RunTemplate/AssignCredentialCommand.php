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

use MultiFlexi\Credential;
use MultiFlexi\CredentialType;
use MultiFlexi\RunTplCreds;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AssignCredentialCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:assign-credential';

    protected function configure(): void
    {
        $this
            ->setName('run-template:assign-credential')
            ->setDescription('Assign a credential to a run template')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'RunTemplate ID')
            ->addOption('credential_id', null, InputOption::VALUE_REQUIRED, 'Credential ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));

        $runtemplateId = $input->getOption('id');
        $credentialId = $input->getOption('credential_id');

        if (empty($runtemplateId) || !is_numeric($runtemplateId)) {
            $msg = 'Missing or invalid --id';
            $output->writeln($format === 'json' ? json_encode(['status' => 'error', 'message' => $msg]) : "<error>{$msg}</error>");

            return self::FAILURE;
        }

        if (empty($credentialId) || !is_numeric($credentialId)) {
            $msg = 'Missing or invalid --credential_id';
            $output->writeln($format === 'json' ? json_encode(['status' => 'error', 'message' => $msg]) : "<error>{$msg}</error>");

            return self::FAILURE;
        }

        $runtemplateId = (int) $runtemplateId;
        $credentialId = (int) $credentialId;

        $credential = new Credential($credentialId);

        if (!$credential->getMyKey()) {
            $msg = "Credential #{$credentialId} not found";
            $output->writeln($format === 'json' ? json_encode(['status' => 'error', 'message' => $msg]) : "<error>{$msg}</error>");

            return self::FAILURE;
        }

        $credentialTypeId = (int) $credential->getDataValue('credential_type_id');
        $credentialType = new CredentialType($credentialTypeId);
        $prototype = (string) $credentialType->getDataValue('prototype');

        $rtCreds = new RunTplCreds();
        $rtCreds->bind($runtemplateId, $credentialId, $prototype);

        if ($format === 'json') {
            $output->writeln(json_encode([
                'status' => 'ok',
                'runtemplate_id' => $runtemplateId,
                'credential_id' => $credentialId,
                'prototype' => $prototype,
            ], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Credential #{$credentialId} assigned to RunTemplate #{$runtemplateId}");
        }

        return self::SUCCESS;
    }
}

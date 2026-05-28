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

use MultiFlexi\RunTplCreds;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnassignCredentialCommand extends BaseCommand
{
    protected static $defaultName = 'run-template:unassign-credential';

    protected function configure(): void
    {
        $this
            ->setName('run-template:unassign-credential')
            ->setDescription('Remove a credential assignment from a run template')
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

        $rtCreds = new RunTplCreds();
        $result = $rtCreds->unbind($runtemplateId, $credentialId);

        if ($format === 'json') {
            $output->writeln(json_encode([
                'status' => $result ? 'ok' : 'not_found',
                'runtemplate_id' => $runtemplateId,
                'credential_id' => $credentialId,
            ], \JSON_PRETTY_PRINT));
        } else {
            if ($result) {
                $output->writeln("Credential #{$credentialId} unassigned from RunTemplate #{$runtemplateId}");
            } else {
                $output->writeln("<comment>No assignment found for Credential #{$credentialId} on RunTemplate #{$runtemplateId}</comment>");
            }
        }

        return self::SUCCESS;
    }
}

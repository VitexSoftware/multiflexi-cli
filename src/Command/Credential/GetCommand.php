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

namespace MultiFlexi\Cli\Command\Credential;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Credential;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'credential:get';

    protected function configure(): void
    {
        $this
            ->setName('credential:get')
            ->setDescription('Get a credential by ID')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential ID')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of fields to display')
            ->addOption('reveal', null, InputOption::VALUE_NONE, 'Show actual secret values instead of masked placeholders (requires confirmation, is audit-logged)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $format === 'json' ? $this->jsonError($output, 'Missing --id') : $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $credential = new Credential((int) $id);

        if (empty($credential->getData())) {
            $format === 'json' ? $this->jsonError($output, 'No credential found with given ID', 'not found') : $output->writeln('<error>No credential found with given ID</error>');

            return self::FAILURE;
        }

        $reveal = (bool) $input->getOption('reveal');

        if ($reveal) {
            $helper = $this->getHelper('question');
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                'This will display credential secrets in your terminal / on-screen history. Continue? [y/N] ',
                false,
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Aborted.</comment>');

                return self::FAILURE;
            }

            if (isset($GLOBALS['securityAuditLogger'])) {
                $GLOBALS['securityAuditLogger']->logEvent(
                    'credential_revealed',
                    "Credential #{$id} revealed via CLI (credential:get --reveal)",
                    'high',
                    null,
                    ['credential_id' => (int) $id],
                );
            }
        }

        $sourceData = $reveal ? $credential->getData() : $credential->getRedactedData();

        $fields = $input->getOption('fields');
        $data = $fields
            ? array_filter($sourceData, static fn ($key) => \in_array($key, explode(',', $fields), true), \ARRAY_FILTER_USE_KEY)
            : $sourceData;

        if ($format === 'json') {
            $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
        } else {
            foreach ($data as $k => $v) {
                $output->writeln("{$k}: {$v}");
            }
        }

        return self::SUCCESS;
    }
}

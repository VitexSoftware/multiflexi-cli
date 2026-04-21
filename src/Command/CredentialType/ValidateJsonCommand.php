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

namespace MultiFlexi\Cli\Command\CredentialType;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateJsonCommand extends BaseCommand
{
    protected static $defaultName = 'credential-type:validate-json';

    protected function configure(): void
    {
        $this
            ->setDescription('Validate a credential type JSON file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $file = $input->getOption('file');

        if (empty($file) || !file_exists($file)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing or invalid --file'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing or invalid --file</error>');
            }

            return self::FAILURE;
        }

        $violations = $this->validateCredTypeJson($file);

        if ($format === 'json') {
            if (empty($violations)) {
                $output->writeln(json_encode(['status' => 'success', 'file' => $file, 'schema' => realpath(\MultiFlexi\CredentialType::$credTypeSchema), 'message' => 'JSON is valid'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln(json_encode(['status' => 'error', 'file' => $file, 'violations' => $violations, 'schema' => realpath(\MultiFlexi\CredentialType::$credTypeSchema), 'message' => 'JSON validation failed'], \JSON_PRETTY_PRINT));
            }
        } else {
            if (empty($violations)) {
                $output->writeln('<info>JSON is valid</info>');
            } else {
                $output->writeln('<error>JSON validation failed</error>');

                foreach ($violations as $v) {
                    $output->writeln('<error> '.$v.' </error>');
                }
            }
        }

        return empty($violations) ? self::SUCCESS : self::FAILURE;
    }
}

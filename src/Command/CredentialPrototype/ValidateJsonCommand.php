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

namespace MultiFlexi\Cli\Command\CredentialPrototype;

use MultiFlexi\CredentialProtoType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateJsonCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:validate-json';

    protected function configure(): void
    {
        $this
            ->setDescription('Validate a credential prototype JSON file against the schema')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $jsonFile = $input->getOption('file');

        if (empty($jsonFile) || !file_exists($jsonFile) || !is_file($jsonFile)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'error', 'message' => 'Missing or invalid --file', 'file' => $jsonFile], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>Missing or invalid --file '.$jsonFile.'</error>');
            }

            return self::FAILURE;
        }

        $result = $this->validateCredPrototypeJson($jsonFile);

        if ($format === 'json') {
            if (empty($result)) {
                $output->writeln(json_encode(['status' => 'success', 'file' => $jsonFile, 'schema' => realpath(CredentialProtoType::$credProtoTypeSchema), 'message' => 'JSON is valid'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln(json_encode(['status' => 'error', 'file' => $jsonFile, 'violations' => $result, 'schema' => realpath(CredentialProtoType::$credProtoTypeSchema), 'message' => 'JSON validation failed'], \JSON_PRETTY_PRINT));
            }
        } else {
            if (empty($result)) {
                $output->writeln('<info>JSON is valid</info>');
            } else {
                $output->writeln('<error>JSON validation failed</error>');

                foreach ($result as $violation) {
                    $output->writeln('<error> '.$violation.' </error>');
                }
            }
        }

        return empty($result) ? self::SUCCESS : self::FAILURE;
    }
}

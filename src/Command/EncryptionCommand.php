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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of EncryptionCommand.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class EncryptionCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'encryption';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Manage encryption keys')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: init')
            ->setHelp('This command manages encryption keys. Use init action to re-initialize encryption keys.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'init':
                return $this->initializeEncryptionKey($output, $format);

            default:
                if ($format === 'json') {
                    $this->jsonError($output, "Unknown action: {$action}");
                } else {
                    $output->writeln("<error>Unknown action: {$action}</error>");
                }

                return MultiFlexiCommand::FAILURE;
        }
    }

    /**
     * Initialize or re-initialize the encryption key for credentials.
     */
    private function initializeEncryptionKey(OutputInterface $output, string $format): int
    {
        try {
            $pdo = \Ease\Shared::db();

            // Generate a new encryption key (256-bit for AES-256)
            $keyData = base64_encode(random_bytes(32));

            // Delete existing credentials key
            $deleteStmt = $pdo->prepare("DELETE FROM encryption_keys WHERE key_name = 'credentials'");
            $deleteStmt->execute();

            // Insert new key
            $insertStmt = $pdo->prepare(
                "INSERT INTO encryption_keys (key_name, key_data, algorithm, created_at, is_active) 
                 VALUES ('credentials', :key_data, 'aes-256-gcm', NOW(), TRUE)"
            );
            $insertStmt->execute(['key_data' => $keyData]);

            if ($format === 'json') {
                $this->jsonSuccess($output, 'Encryption key initialized successfully', [
                    'key_name' => 'credentials',
                    'algorithm' => 'aes-256-gcm',
                    'initialized' => true,
                ]);
            } else {
                $output->writeln('<info>Encryption key initialized successfully</info>');
                $output->writeln('Key name: credentials');
                $output->writeln('Algorithm: aes-256-gcm');
            }

            return MultiFlexiCommand::SUCCESS;
        } catch (\Exception $e) {
            if ($format === 'json') {
                $this->jsonError($output, 'Failed to initialize encryption key: ' . $e->getMessage());
            } else {
                $output->writeln('<error>Failed to initialize encryption key: ' . $e->getMessage() . '</error>');
            }

            return MultiFlexiCommand::FAILURE;
        }
    }
}

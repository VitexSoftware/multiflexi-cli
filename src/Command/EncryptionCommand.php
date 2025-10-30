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
            ->addArgument('action', InputArgument::REQUIRED, 'Action: status, init')
            ->setHelp('This command manages encryption keys. Actions: status (check status), init (re-initialize keys).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'status':
                return $this->showEncryptionStatus($output, $format);

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
     * Show encryption system status.
     */
    private function showEncryptionStatus(OutputInterface $output, string $format): int
    {
        try {
            $engine = new \MultiFlexi\Engine();
            $pdo = $engine->getPdo();

            // Check ENCRYPTION_MASTER_KEY
            $masterKey = $this->getMasterKey();
            $masterKeyStatus = $masterKey ? 'configured' : 'missing';

            // Check encryption_keys table
            $stmt = $pdo->query("SELECT key_name, algorithm, created_at, rotated_at, is_active FROM encryption_keys ORDER BY created_at DESC");
            $keys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $activeKeys = array_filter($keys, fn($key) => $key['is_active']);

            if ($format === 'json') {
                $this->jsonSuccess($output, 'Encryption status retrieved', [
                    'master_key' => $masterKeyStatus,
                    'total_keys' => count($keys),
                    'active_keys' => count($activeKeys),
                    'keys' => $keys,
                ]);
            } else {
                $output->writeln('<info>Encryption Status</info>');
                $output->writeln('Master Key: ' . $masterKeyStatus);
                $output->writeln('Total Keys: ' . count($keys));
                $output->writeln('Active Keys: ' . count($activeKeys));
                $output->writeln('');

                if (!empty($keys)) {
                    $output->writeln('Keys:');
                    $table = [];
                    foreach ($keys as $key) {
                        $table[] = [
                            $key['key_name'],
                            $key['algorithm'],
                            $key['is_active'] ? 'active' : 'inactive',
                            $key['created_at'],
                            $key['rotated_at'] ?? 'never',
                        ];
                    }
                    $output->writeln(self::outputTable($table, 200, ['Key Name', 'Algorithm', 'Status', 'Created', 'Rotated']));
                }
            }

            return MultiFlexiCommand::SUCCESS;
        } catch (\Exception $e) {
            if ($format === 'json') {
                $this->jsonError($output, 'Failed to retrieve encryption status: ' . $e->getMessage());
            } else {
                $output->writeln('<error>Failed to retrieve encryption status: ' . $e->getMessage() . '</error>');
            }

            return MultiFlexiCommand::FAILURE;
        }
    }

    /**
     * Initialize or re-initialize the encryption key for credentials.
     * 
     * WARNING: This will invalidate all existing encrypted credentials!
     */
    private function initializeEncryptionKey(OutputInterface $output, string $format): int
    {
        try {
            // Check if master key is configured
            $masterKey = $this->getMasterKey();
            if (!$masterKey) {
                $errorMsg = 'ENCRYPTION_MASTER_KEY is not configured. Set it in .env file or as environment variable.';
                if ($format === 'json') {
                    $this->jsonError($output, $errorMsg);
                } else {
                    $output->writeln('<error>' . $errorMsg . '</error>');
                }
                return MultiFlexiCommand::FAILURE;
            }

            $engine = new \MultiFlexi\Engine();
            $pdo = $engine->getPdo();

            // Encrypt the key using master key (AES-256-CBC)
            $key = random_bytes(32);
            $iv = random_bytes(16);
            $hashedMasterKey = hash('sha256', $masterKey, true);
            $encryptedKey = openssl_encrypt($key, 'aes-256-cbc', $hashedMasterKey, OPENSSL_RAW_DATA, $iv);
            
            if ($encryptedKey === false) {
                throw new \RuntimeException('Failed to encrypt key: ' . openssl_error_string());
            }

            $keyData = base64_encode($iv . $encryptedKey);

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
                    'warning' => 'All existing encrypted credentials are now invalid and must be re-entered',
                ]);
            } else {
                $output->writeln('<info>Encryption key initialized successfully</info>');
                $output->writeln('Key name: credentials');
                $output->writeln('Algorithm: aes-256-gcm');
                $output->writeln('<comment>WARNING: All existing encrypted credentials are now invalid and must be re-entered</comment>');
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

    /**
     * Get master key from environment or config.
     */
    private function getMasterKey(): ?string
    {
        $masterKey = getenv('ENCRYPTION_MASTER_KEY');
        if ($masterKey) {
            return $masterKey;
        }

        $masterKey = getenv('MULTIFLEXI_MASTER_KEY');
        if ($masterKey) {
            return $masterKey;
        }

        return \Ease\Shared::cfg('ENCRYPTION_MASTER_KEY');
    }
}

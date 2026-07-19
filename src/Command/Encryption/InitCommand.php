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

namespace MultiFlexi\Cli\Command\Encryption;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends BaseCommand
{
    protected static $defaultName = 'encryption:init';

    /**
     * Must match the placeholder value used by EncryptionKeysSeeder in multiflexi-database.
     */
    private const PLACEHOLDER = 'PLACEHOLDER_KEY_TO_BE_REPLACED';

    protected function configure(): void
    {
        $this
            ->setName('encryption:init')
            ->setDescription('Initialize the encryption key for credentials (no-op if already initialized)')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Rotate the key even if one is already initialized (invalidates existing encrypted credentials)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $force = (bool) $input->getOption('force');

        try {
            $masterKey = self::getMasterKey();

            if (!$masterKey) {
                $errorMsg = 'ENCRYPTION_MASTER_KEY is not configured. Set it in .env file or as environment variable.';

                if ($format === 'json') {
                    $this->jsonError($output, $errorMsg);
                } else {
                    $output->writeln('<error>'.$errorMsg.'</error>');
                }

                return self::FAILURE;
            }

            $engine = new \MultiFlexi\Engine();
            $pdo = $engine->getPdo();

            $existing = $pdo->prepare("SELECT key_data FROM encryption_keys WHERE key_name = 'credentials' AND is_active = TRUE LIMIT 1");
            $existing->execute();
            $existingKeyData = $existing->fetchColumn();
            $alreadyInitialized = $existingKeyData !== false && $existingKeyData !== null && $existingKeyData !== '' && $existingKeyData !== self::PLACEHOLDER;

            if ($alreadyInitialized && !$force) {
                $msg = 'Credentials encryption key is already initialized; skipping. Use --force to rotate it (this invalidates existing encrypted credentials).';

                if ($format === 'json') {
                    $this->jsonSuccess($output, $msg, ['key_name' => 'credentials', 'initialized' => false, 'rotated' => false]);
                } else {
                    $output->writeln('<comment>'.$msg.'</comment>');
                }

                return self::SUCCESS;
            }

            // Delegate to DataEncryption::generateKey(), which versions the
            // key row (deactivate old version, insert new one) instead of
            // deleting prior key material — deleting it here would
            // permanently break decryption of anything already encrypted
            // under the key being replaced.
            $dataEncryption = new \MultiFlexi\Security\DataEncryption($pdo);
            $dataEncryption->generateKey('credentials', \MultiFlexi\Security\DataEncryption::ALGORITHM_AES_256_GCM);

            $successMsg = $alreadyInitialized ? 'Encryption key rotated successfully' : 'Encryption key initialized successfully';

            if ($format === 'json') {
                $payload = [
                    'key_name' => 'credentials',
                    'algorithm' => 'aes-256-gcm',
                    'initialized' => true,
                    'rotated' => $alreadyInitialized,
                ];

                if ($alreadyInitialized) {
                    $payload['warning'] = 'All existing encrypted credentials are now invalid and must be re-entered';
                }

                $this->jsonSuccess($output, $successMsg, $payload);
            } else {
                $output->writeln('<info>'.$successMsg.'</info>');
                $output->writeln('Key name: credentials');
                $output->writeln('Algorithm: aes-256-gcm');

                if ($alreadyInitialized) {
                    $output->writeln('<comment>WARNING: All existing encrypted credentials are now invalid and must be re-entered</comment>');
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            if ($format === 'json') {
                $this->jsonError($output, 'Failed to initialize encryption key: '.$e->getMessage());
            } else {
                $output->writeln('<error>Failed to initialize encryption key: '.$e->getMessage().'</error>');
            }

            return self::FAILURE;
        }
    }
}

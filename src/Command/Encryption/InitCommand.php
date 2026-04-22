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

    protected function configure(): void
    {
        $this
            ->setName('encryption:init')
            ->setDescription('Initialize or re-initialize the encryption key for credentials')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));

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

            $key = random_bytes(32);
            $iv = random_bytes(16);
            $hashedMasterKey = hash('sha256', $masterKey, true);
            $encryptedKey = openssl_encrypt($key, 'aes-256-cbc', $hashedMasterKey, \OPENSSL_RAW_DATA, $iv);

            if ($encryptedKey === false) {
                throw new \RuntimeException('Failed to encrypt key: '.openssl_error_string());
            }

            $keyData = base64_encode($iv.$encryptedKey);

            $deleteStmt = $pdo->prepare("DELETE FROM encryption_keys WHERE key_name = 'credentials'");
            $deleteStmt->execute();

            $insertStmt = $pdo->prepare(<<<'EOD'
INSERT INTO encryption_keys (key_name, key_data, algorithm, created_at, is_active)
                 VALUES ('credentials', :key_data, 'aes-256-gcm', NOW(), TRUE)
EOD,);
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

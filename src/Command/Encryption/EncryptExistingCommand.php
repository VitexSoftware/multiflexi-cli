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

/**
 * Backfills plaintext `credata.value` rows (written before encryption at
 * rest was wired in) into encrypted envelopes, for redactable (secret/
 * password) fields only. Idempotent: rows already marked is_encrypted are
 * skipped, so it is safe to re-run.
 */
class EncryptExistingCommand extends BaseCommand
{
    protected static $defaultName = 'credential:encrypt-existing';

    protected function configure(): void
    {
        $this
            ->setName('credential:encrypt-existing')
            ->setDescription('Encrypt plaintext credential values stored before encryption at rest was enabled')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report how many rows would be affected without changing anything')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Rows processed per transaction batch', '200');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $dryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        if (!self::getMasterKey()) {
            $msg = 'ENCRYPTION_MASTER_KEY is not configured. Run encryption:init first.';

            return $this->fail($output, $format, $msg);
        }

        $engine = new \MultiFlexi\Engine();
        $pdo = $engine->getPdo();
        $encryptor = new \MultiFlexi\Security\DataEncryption($pdo);

        // Only rows belonging to a field the CredentialType marks as
        // redactable (secret/password) need encrypting; non-secret config
        // values are left as plaintext to limit blast radius and cost.
        $rows = $pdo->query(
            "SELECT cd.id, cd.credential_id, cd.name, cd.value, cd.type, c.credential_type_id
             FROM credata cd
             JOIN credentials c ON c.id = cd.credential_id
             WHERE cd.is_encrypted = 0 AND cd.value IS NOT NULL AND cd.value != ''
             ORDER BY cd.id",
        )->fetchAll(\PDO::FETCH_ASSOC);

        $typeFieldsCache = [];
        $toEncrypt = [];

        foreach ($rows as $row) {
            $credTypeId = (int) $row['credential_type_id'];

            if (!isset($typeFieldsCache[$credTypeId])) {
                $typeFieldsCache[$credTypeId] = (new \MultiFlexi\CredentialType($credTypeId))->getFields();
            }

            $field = $typeFieldsCache[$credTypeId]->getFieldByCode($row['name']);
            $isRedactable = $field ? $field->isRedactable() : \in_array($row['type'], ['password', 'secret'], true);

            if ($isRedactable) {
                $toEncrypt[] = $row;
            }
        }

        if ($dryRun) {
            $msg = \sprintf('%d row(s) would be encrypted (out of %d plaintext row(s) scanned).', \count($toEncrypt), \count($rows));

            return $this->succeed($output, $format, $msg, ['scanned' => \count($rows), 'to_encrypt' => \count($toEncrypt)]);
        }

        $encrypted = 0;
        $failed = 0;
        $updateStmt = $pdo->prepare('UPDATE credata SET value = ?, is_encrypted = 1, encryption_key_version = ? WHERE id = ?');

        foreach (array_chunk($toEncrypt, $batchSize) as $batch) {
            $pdo->beginTransaction();

            try {
                foreach ($batch as $row) {
                    try {
                        $envelope = $encryptor->encrypt((string) $row['value'], 'credentials');
                        $updateStmt->execute([
                            json_encode($envelope, \JSON_THROW_ON_ERROR),
                            $envelope['key_version'] ?? null,
                            $row['id'],
                        ]);
                        ++$encrypted;
                    } catch (\Throwable $e) {
                        ++$failed;
                        error_log(\sprintf('credential:encrypt-existing: failed to encrypt credata.id=%d: %s', $row['id'], $e->getMessage()));
                    }
                }

                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();

                return $this->fail($output, $format, 'Batch failed and was rolled back: '.$e->getMessage());
            }
        }

        $msg = \sprintf('Encrypted %d row(s); %d failed.', $encrypted, $failed);

        return $this->succeed($output, $format, $msg, ['encrypted' => $encrypted, 'failed' => $failed]);
    }

    private function succeed(OutputInterface $output, string $format, string $message, array $data): int
    {
        if ($format === 'json') {
            $this->jsonSuccess($output, $message, $data);
        } else {
            $output->writeln('<info>'.$message.'</info>');
        }

        return self::SUCCESS;
    }

    private function fail(OutputInterface $output, string $format, string $message): int
    {
        if ($format === 'json') {
            $this->jsonError($output, $message);
        } else {
            $output->writeln('<error>'.$message.'</error>');
        }

        return self::FAILURE;
    }
}

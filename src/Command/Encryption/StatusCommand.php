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

class StatusCommand extends BaseCommand
{
    protected static $defaultName = 'encryption:status';

    protected function configure(): void
    {
        $this
            ->setDescription('Show encryption key status')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));

        try {
            $engine = new \MultiFlexi\Engine();
            $pdo = $engine->getPdo();

            $masterKey = self::getMasterKey();
            $masterKeyStatus = $masterKey ? 'configured' : 'missing';

            $stmt = $pdo->query('SELECT key_name, algorithm, created_at, rotated_at, is_active FROM encryption_keys ORDER BY created_at DESC');
            $keys = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $activeKeys = array_filter($keys, static fn ($key) => $key['is_active']);

            if ($format === 'json') {
                $this->jsonSuccess($output, 'Encryption status retrieved', [
                    'master_key' => $masterKeyStatus,
                    'total_keys' => \count($keys),
                    'active_keys' => \count($activeKeys),
                    'keys' => $keys,
                ]);
            } else {
                $output->writeln('<info>Encryption Status</info>');
                $output->writeln('Master Key: '.$masterKeyStatus);
                $output->writeln('Total Keys: '.\count($keys));
                $output->writeln('Active Keys: '.\count($activeKeys));

                if (!empty($keys)) {
                    $output->writeln('');
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

            return self::SUCCESS;
        } catch (\Exception $e) {
            if ($format === 'json') {
                $this->jsonError($output, 'Failed to retrieve encryption status: '.$e->getMessage());
            } else {
                $output->writeln('<error>Failed to retrieve encryption status: '.$e->getMessage().'</error>');
            }

            return self::FAILURE;
        }
    }
}

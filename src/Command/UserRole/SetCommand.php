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

namespace MultiFlexi\Cli\Command\UserRole;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Rbac;
use MultiFlexi\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'user-role:set';

    protected function configure(): void
    {
        $this
            ->setName('user-role:set')
            ->setDescription('Set RBAC roles for a user (replace existing by default)')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'User login (alternative to --user_id)')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email (alternative to --user_id)')
            ->addOption('roles', null, InputOption::VALUE_REQUIRED, 'Comma-separated RBAC role names (e.g. admin,viewer)')
            ->addOption('replace', null, InputOption::VALUE_OPTIONAL, 'Replace existing roles (true/false), default true', 'true')
            ->addOption('assigned_by', null, InputOption::VALUE_OPTIONAL, 'Assigned-by user ID (optional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $userId = self::resolveUserId($input);

        if ($userId <= 0) {
            $msg = 'Provide --user_id or --login or --email';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $user = new User($userId);

        if (empty($user->getData())) {
            $msg = "User #{$userId} not found";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $rolesInput = trim((string) $input->getOption('roles'));
        $roleNames = $rolesInput === '' ? [] : array_values(array_unique(array_filter(array_map('trim', explode(',', $rolesInput)))));
        $replace = (bool) $this->parseBoolOption($input->getOption('replace'));
        $assignedBy = $input->getOption('assigned_by');
        $assignedBy = ($assignedBy !== null && $assignedBy !== '' && is_numeric($assignedBy)) ? (int) $assignedBy : null;

        $pdo = self::connectPdo();

        if (!self::tableExists($pdo, 'rbac_roles') || !self::tableExists($pdo, 'rbac_user_roles')) {
            $msg = 'RBAC tables are missing (rbac_roles/rbac_user_roles). Ensure RBAC is initialized first.';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        try {
            $finalRoles = (new Rbac())->setUserRoles($userId, $roleNames, $replace, $assignedBy);
        } catch (\InvalidArgumentException $e) {
            $format === 'json' ? $this->jsonError($output, $e->getMessage(), 'invalid_roles') : $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        } catch (\Throwable $e) {
            $msg = 'Failed to set user roles: '.$e->getMessage();
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        if ($format === 'json') {
            $this->jsonSuccess($output, 'RBAC roles updated', [
                'user_id' => $userId,
                'replace' => $replace,
                'roles' => array_column($finalRoles, 'name'),
                'role_details' => $finalRoles,
            ]);
        } else {
            $output->writeln('RBAC roles updated for user #'.$userId);
            $output->writeln('Roles: '.(empty($finalRoles) ? '(none)' : implode(', ', array_column($finalRoles, 'name'))));
        }

        return self::SUCCESS;
    }

    private static function resolveUserId(InputInterface $input): int
    {
        $userId = (int) $input->getOption('user_id');

        if ($userId > 0) {
            return $userId;
        }

        $login = trim((string) $input->getOption('login'));
        $email = trim((string) $input->getOption('email'));

        if ($login !== '') {
            $found = (new User())->listingQuery()->where(['login' => $login])->fetch();

            return $found ? (int) $found['id'] : 0;
        }

        if ($email !== '') {
            $found = (new User())->listingQuery()->where(['email' => $email])->fetch();

            return $found ? (int) $found['id'] : 0;
        }

        return 0;
    }

    private static function connectPdo(): \PDO
    {
        return new \PDO(
            \Ease\Shared::cfg('DB_CONNECTION').':host='.\Ease\Shared::cfg('DB_HOST').';port='.(string) \Ease\Shared::cfg('DB_PORT', 3306).';dbname='.\Ease\Shared::cfg('DB_DATABASE').';charset=utf8mb4',
            \Ease\Shared::cfg('DB_USERNAME'),
            \Ease\Shared::cfg('DB_PASSWORD'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        );
    }

    private static function tableExists(\PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
        $stmt->execute([\Ease\Shared::cfg('DB_DATABASE'), $table]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

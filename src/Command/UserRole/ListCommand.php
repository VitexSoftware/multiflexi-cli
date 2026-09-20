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

/**
 * List RBAC roles assigned to a user (and the available role catalog).
 */
class ListCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'user-role:list';

    protected function configure(): void
    {
        $this
            ->setName('user-role:list')
            ->setDescription('List RBAC roles assigned to a user')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'User login (alternative to --user_id)')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email (alternative to --user_id)');
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

        $pdo = self::connectPdo();

        if (!self::tableExists($pdo, 'rbac_roles') || !self::tableExists($pdo, 'rbac_user_roles')) {
            $msg = 'RBAC tables are missing (rbac_roles/rbac_user_roles). Ensure RBAC is initialized first.';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $rbac = new Rbac();
        $roleDetails = $rbac->getUserRoleDetails($userId);
        $roleNames = array_column($roleDetails, 'name');
        $available = array_keys($rbac->getAvailableRoles());
        sort($available);

        $payload = [
            'user_id' => $userId,
            'login' => (string) $user->getDataValue('login'),
            'roles' => $roleNames,
            'role_details' => $roleDetails,
            'available_roles' => $available,
        ];

        if ($format === 'json') {
            $output->writeln(json_encode($payload, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln(sprintf('User #%d (%s)', $userId, $payload['login']));
            $output->writeln('Roles: '.(empty($roleNames) ? '(none)' : implode(', ', $roleNames)));
            $output->writeln('Available: '.implode(', ', $available));

            if (!empty($roleDetails)) {
                $output->writeln('');
                $output->writeln(self::outputTable($roleDetails));
            }
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
}

<?php

declare(strict_types=1);

namespace MultiFlexi\Cli\Command\UserCompany;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Company;
use MultiFlexi\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AssignCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'user-company:assign';

    protected function configure(): void
    {
        $this
            ->setName('user-company:assign')
            ->setDescription('Assign a user to a company')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('company_id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'User login (alternative to --user_id)')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email (alternative to --user_id)')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'Assignment role in company_user table', 'viewer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $companyId = (int) $input->getOption('company_id');
        $role = (string) $input->getOption('role');

        if ($companyId <= 0) {
            $msg = 'Missing or invalid --company_id';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $company = new Company($companyId);

        if (empty($company->getData())) {
            $msg = "Company #{$companyId} not found";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $userId = $this->resolveUserId($input);

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

        $pdo = $this->connectPdo();

        if (!$this->tableExists($pdo, 'company_user')) {
            $msg = 'Table company_user does not exist. Run database migrations first.';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $sql = 'INSERT INTO company_user (company_id, user_id, role) VALUES (?, ?, ?) '
            .'ON DUPLICATE KEY UPDATE role = VALUES(role)';
        $ok = $pdo->prepare($sql)->execute([$companyId, $userId, $role]);

        if (!$ok) {
            $msg = 'Failed to assign user to company';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        if ($format === 'json') {
            $this->jsonSuccess($output, 'User assigned to company', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'role' => $role,
            ]);
        } else {
            $output->writeln("User #{$userId} assigned to company #{$companyId} with role '{$role}'");
        }

        return self::SUCCESS;
    }

    private function resolveUserId(InputInterface $input): int
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

    private function connectPdo(): \PDO
    {
        return new \PDO(
            \Ease\Shared::cfg('DB_CONNECTION').':host='.\Ease\Shared::cfg('DB_HOST').';port='.(string) \Ease\Shared::cfg('DB_PORT', 3306).';dbname='.\Ease\Shared::cfg('DB_DATABASE').';charset=utf8mb4',
            \Ease\Shared::cfg('DB_USERNAME'),
            \Ease\Shared::cfg('DB_PASSWORD'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        );
    }

    private function tableExists(\PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
        $stmt->execute([\Ease\Shared::cfg('DB_DATABASE'), $table]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

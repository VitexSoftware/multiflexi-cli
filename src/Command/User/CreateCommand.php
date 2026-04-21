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

namespace MultiFlexi\Cli\Command\User;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'user:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create a user')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Login')
            ->addOption('firstname', null, InputOption::VALUE_REQUIRED, 'First name')
            ->addOption('lastname', null, InputOption::VALUE_REQUIRED, 'Last name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Hashed password')
            ->addOption('plaintext', null, InputOption::VALUE_REQUIRED, 'Plaintext password')
            ->addOption('enabled', null, InputOption::VALUE_OPTIONAL, 'Enabled (true/false)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $data = [];

        foreach (['login', 'firstname', 'lastname', 'email', 'enabled'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $field === 'enabled' ? $this->parseBoolOption($val) : $val;
            }
        }

        if (empty($data['login'])) {
            $output->writeln('<error>Missing --login</error>');

            return self::FAILURE;
        }

        if (empty($data['email'])) {
            $output->writeln('<error>Missing --email</error>');

            return self::FAILURE;
        }

        $plaintextPassword = $input->getOption('plaintext');

        if (\is_string($plaintextPassword)) {
            $data['password'] = User::encryptPassword($plaintextPassword);
        } elseif ($input->getOption('password')) {
            $data['password'] = $input->getOption('password');
        }

        $user = new User();
        $user->takeData($data);
        $userId = $user->saveToSQL();

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = (new User((int) $userId))->getData();

            if ($format === 'json') {
                $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['user_id' => $userId], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("User created with ID: {$userId}");
            }
        }

        return self::SUCCESS;
    }
}

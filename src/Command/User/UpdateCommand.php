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

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'user:update';

    protected function configure(): void
    {
        $this
            ->setDescription('Update a user')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'User ID')
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
        $id = $input->getOption('id');
        $login = $input->getOption('login');

        if (empty($id) && empty($login)) {
            $output->writeln('<error>Missing --id or --login</error>');

            return self::FAILURE;
        }

        $user = null;

        if (!empty($id)) {
            $user = new User((int) $id);
        } elseif (!empty($login)) {
            $user = new User($login);
            $id = $user->getDataValue('id');
        }

        if (!$user || empty($user->getData())) {
            $output->writeln('<error>No user found with the given identifier</error>');

            return self::FAILURE;
        }

        $data = [];

        foreach (['login', 'firstname', 'lastname', 'email', 'enabled'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $field === 'enabled' ? $this->parseBoolOption($val) : $val;
            }
        }

        $plaintextPassword = $input->getOption('plaintext');

        if (\is_string($plaintextPassword)) {
            $data['password'] = User::encryptPassword($plaintextPassword);
        } elseif ($input->getOption('password')) {
            $data['password'] = $input->getOption('password');
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        $user->updateToSQL($data, ['id' => $id]);

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            if ($format === 'json') {
                $output->writeln(json_encode($user->getData(), \JSON_PRETTY_PRINT));
            } else {
                foreach ($user->getData() as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['updated' => true, 'user_id' => $id], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("User updated successfully (ID: {$id})");
            }
        }

        return self::SUCCESS;
    }
}

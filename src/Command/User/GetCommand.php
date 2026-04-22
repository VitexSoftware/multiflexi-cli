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

class GetCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'user:get';

    protected function configure(): void
    {
        $this
            ->setName('user:get')
            ->setDescription('Get a user by ID, login, or email')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Login')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $login = $input->getOption('login');
        $email = $input->getOption('email');

        if (!empty($id)) {
            $user = new User((int) $id);
        } elseif (!empty($login)) {
            $found = (new User())->listingQuery()->where(['login' => $login])->fetch();
            $user = $found ? new User($found['id']) : null;
        } elseif (!empty($email)) {
            $found = (new User())->listingQuery()->where(['email' => $email])->fetch();
            $user = $found ? new User($found['id']) : null;
        } else {
            $output->writeln('<error>Missing --id, --login or --email</error>');

            return self::FAILURE;
        }

        if (empty($user) || empty($user->getData())) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'not found', 'message' => 'No user found with given identifier'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>No user found with given identifier</error>');
            }

            return self::FAILURE;
        }

        $fields = $input->getOption('fields');

        if ($fields) {
            $fieldsArray = explode(',', $fields);
            $data = array_filter($user->getData(), static fn ($key) => \in_array($key, $fieldsArray, true), \ARRAY_FILTER_USE_KEY);
        } else {
            $data = $user->getData();
        }

        if ($format === 'json') {
            $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
        } else {
            foreach ($data as $k => $v) {
                $output->writeln("{$k}: {$v}");
            }
        }

        return self::SUCCESS;
    }
}

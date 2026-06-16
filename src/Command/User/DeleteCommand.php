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

class DeleteCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'user:delete';

    protected function configure(): void
    {
        $this
            ->setName('user:delete')
            ->setDescription('Delete a user')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Login');
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
            $userData = (new User())->listingQuery()->where('login', $login)->fetch();

            if ($userData) {
                $user = new User((int) $userData['id']);
            }
        }

        if (!$user || empty($user->getData())) {
            if ($format === 'json') {
                $output->writeln(json_encode(['status' => 'failure', 'message' => 'No user found with the given identifier'], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('<error>No user found with the given identifier</error>');
            }

            return self::FAILURE;
        }

        $user->deleteFromSQL();

        if ($format === 'json') {
            $output->writeln(json_encode(['status' => 'success', 'message' => 'User deleted successfully'], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('<info>User deleted successfully</info>');
        }

        return self::SUCCESS;
    }
}

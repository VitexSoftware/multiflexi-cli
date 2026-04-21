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

namespace MultiFlexi\Cli\Command\Token;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Token;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'token:update';

    protected function configure(): void
    {
        $this
            ->setDescription('Update a token')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Token ID')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'Token value');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $data = [];
        $user = $input->getOption('user');

        if ($user !== null) {
            $data['user_id'] = $user;
        }

        $tokenVal = $input->getOption('token');

        if ($tokenVal !== null) {
            $data['token'] = $tokenVal;
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        $token = new Token((int) $id);
        $token->updateToSQL($data, ['id' => $id]);

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = $token->getData();

            if ($format === 'json') {
                $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['updated' => true, 'token_id' => $id], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Token updated: ID={$id}");
            }
        }

        return self::SUCCESS;
    }
}

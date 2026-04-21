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

class CreateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'token:create';

    protected function configure(): void
    {
        $this
            ->setDescription('Create a token')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'Token value');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $user = $input->getOption('user');

        if (empty($user)) {
            $output->writeln('<error>Missing --user</error>');

            return self::FAILURE;
        }

        $data = ['user_id' => $user];
        $tokenVal = $input->getOption('token');

        if ($tokenVal !== null) {
            $data['token'] = $tokenVal;
        }

        $token = new Token();
        $token->takeData($data);

        if (empty($tokenVal)) {
            $token->generate();
        }

        $tokenId = $token->saveToSQL();

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $full = (new Token((int) $tokenId))->getData();

            if ($format === 'json') {
                $output->writeln(json_encode($full, \JSON_PRETTY_PRINT));
            } else {
                foreach ($full as $k => $v) {
                    $output->writeln("{$k}: {$v}");
                }
            }
        } else {
            if ($format === 'json') {
                $output->writeln(json_encode(['token_id' => $tokenId], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln("Token created with ID: {$tokenId}");
            }
        }

        return self::SUCCESS;
    }
}

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

class GenerateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'token:generate';

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a new token for a user')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $user = $input->getOption('user');

        if (empty($user)) {
            $output->writeln('<error>Missing --user</error>');

            return self::FAILURE;
        }

        $token = new Token();
        $token->setDataValue('user_id', $user);
        $token->generate();
        $tokenId = $token->saveToSQL();

        if ($format === 'json') {
            $output->writeln(json_encode(['token_id' => $tokenId, 'token' => $token->getDataValue('token')], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Token generated for user: {$user}");
            $output->writeln("Token ID: {$tokenId}");
            $output->writeln("Token: {$token->getDataValue('token')}");
        }

        return self::SUCCESS;
    }
}

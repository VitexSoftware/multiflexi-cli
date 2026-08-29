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
use MultiFlexi\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'token:generate';

    protected function configure(): void
    {
        $this
            ->setName('token:generate')
            ->setDescription('Generate a new API bearer token for a user (printed once)')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'User login (alternative to --user)')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email (alternative to --user)')
            ->addOption('ttl', null, InputOption::VALUE_OPTIONAL, 'Token lifetime, as a strtotime()-relative expression (e.g. "+90 days"); omit for a token that never expires');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $userId = self::resolveUserId($input);

        if ($userId <= 0) {
            $msg = 'Provide --user or --login or --email';
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $user = new User($userId);

        if (empty($user->getData())) {
            $msg = "User #{$userId} not found";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        if (!$user->isAccountEnabled()) {
            $msg = "User #{$userId} account is disabled - refusing to issue a token";
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

            return self::FAILURE;
        }

        $ttl = trim((string) $input->getOption('ttl'));
        $until = null;

        if ($ttl !== '') {
            $timestamp = strtotime($ttl);

            if ($timestamp === false) {
                $msg = "Invalid --ttl expression: {$ttl}";
                $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln("<error>{$msg}</error>");

                return self::FAILURE;
            }

            $until = date('Y-m-d H:i:s', $timestamp);
        }

        $token = new Token();
        $token->setDataValue('user_id', $userId);
        $token->generate();

        if ($until !== null) {
            $token->setDataValue('until', $until);
        }

        $tokenId = $token->saveToSQL();
        $tokenString = $token->getDataValue('token');

        if ($format === 'json') {
            $output->writeln(json_encode([
                'token_id' => $tokenId,
                'user_id' => $userId,
                'token' => $tokenString,
                'until' => $until,
            ], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('Token generated for user #'.$userId.' (save it now, it will not be shown again):');
            $output->writeln($tokenString);

            if ($until !== null) {
                $output->writeln('Expires: '.$until);
            }
        }

        return self::SUCCESS;
    }

    private static function resolveUserId(InputInterface $input): int
    {
        $userId = (int) $input->getOption('user');

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

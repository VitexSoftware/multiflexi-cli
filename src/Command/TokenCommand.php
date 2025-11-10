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

namespace MultiFlexi\Cli\Command;

use MultiFlexi\Token;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of TokenCommand.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
// Přidání TokenCommand pro správu tokenů
class TokenCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'token';
    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Manage tokens')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|create|generate|update')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Token ID')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User ID')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'Token value')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of results for list action')
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Sort order for list action: A (ascending) or D (descending)');
        // Add more options as needed
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'list':
                $token = new Token();
                $query = $token->listingQuery();
                
                // Handle order option
                $order = $input->getOption('order');
                if (!empty($order)) {
                    $orderBy = strtoupper($order) === 'D' ? 'DESC' : 'ASC';
                    $query = $query->orderBy('id ' . $orderBy);
                }
                
                // Handle limit option
                $limit = $input->getOption('limit');
                if (!empty($limit) && is_numeric($limit)) {
                    $query = $query->limit((int) $limit);
                }
                
                // Handle offset option
                $offset = $input->getOption('offset');
                if (!empty($offset) && is_numeric($offset)) {
                    $query = $query->offset((int) $offset);
                }
                
                $tokens = $query->fetchAll();
                
                // Handle fields option
                $fields = $input->getOption('fields');
                if (!empty($fields)) {
                    $fieldList = array_map('trim', explode(',', $fields));
                    $tokens = array_map(function($token) use ($fieldList) {
                        return array_intersect_key($token, array_flip($fieldList));
                    }, $tokens);
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($tokens, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln(self::outputTable($tokens));
                }

                return MultiFlexiCommand::SUCCESS;
            case 'get':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for token get</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $token = new Token((int) $id);
                $data = $token->getData();

                if ($format === 'json') {
                    $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
                } else {
                    foreach ($data as $k => $v) {
                        $output->writeln("{$k}: {$v}");
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'create':
                $data = [];

                foreach (['user', 'token'] as $field) {
                    $val = $input->getOption($field);

                    if ($val !== null) {
                        $data[$field] = $val;
                    }
                }

                if (empty($data['user'])) {
                    $output->writeln('<error>Missing --user for token create</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $token = new \MultiFlexi\Token();
                $token->takeData($data);
                $tokenId = $token->saveToSQL();

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $full = (new \MultiFlexi\Token((int) $tokenId))->getData();

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

                return MultiFlexiCommand::SUCCESS;
            case 'generate':
                $user = $input->getOption('user');

                if (empty($user)) {
                    $output->writeln('<error>Missing --user for token generate</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $token = new \MultiFlexi\Token();
                $token->setDataValue('user', $user);
                $token->generate();
                $tokenId = $token->saveToSQL();

                if ($format === 'json') {
                    $output->writeln(json_encode(['token_id' => $tokenId, 'token' => $token->getDataValue('token')], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln("Token generated for user: {$user}");
                    $output->writeln("Token ID: {$tokenId}");
                    $output->writeln("Token: {$token->getDataValue('token')}");
                }

                return MultiFlexiCommand::SUCCESS;
            case 'update':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for token update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $data = [];

                foreach (['user', 'token'] as $field) {
                    $val = $input->getOption($field);

                    if ($val !== null) {
                        $data[$field] = $val;
                    }
                }

                if (empty($data)) {
                    $output->writeln('<error>No fields to update</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $token = new \MultiFlexi\Token((int) $id);
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

                return MultiFlexiCommand::SUCCESS;
            case 'delete':
                $id = $input->getOption('id');

                if (empty($id)) {
                    $output->writeln('<error>Missing --id for token delete</error>');

                    return MultiFlexiCommand::FAILURE;
                }

                $token = new \MultiFlexi\Token((int) $id);
                $token->deleteFromSQL();

                if ($format === 'json') {
                    $output->writeln(json_encode(['deleted' => true, 'token_id' => $id], \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln("Token deleted: ID={$id}");
                }

                return MultiFlexiCommand::SUCCESS;

            default:
                $output->writeln("<error>Unknown action: {$action}</error>");

                return MultiFlexiCommand::FAILURE;
        }
    }
}

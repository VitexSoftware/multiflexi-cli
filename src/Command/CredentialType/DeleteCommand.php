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

namespace MultiFlexi\Cli\Command\CredentialType;

use MultiFlexi\CredentialType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends BaseCommand
{
    protected static $defaultName = 'credential-type:delete';

    protected function configure(): void
    {
        $this
            ->setDescription('Delete a credential type')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential Type ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        $credType = new CredentialType();

        if ($credType->deleteFromSQL((int) $id)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['deleted' => true], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('Credential type deleted successfully');
            }

            return self::SUCCESS;
        }

        $output->writeln('<error>Failed to delete credential type</error>');

        return self::FAILURE;
    }
}

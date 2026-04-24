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

class UpdateCommand extends BaseCommand
{
    protected static $defaultName = 'credential-type:update';

    protected function configure(): void
    {
        $this
            ->setName('credential-type:update')
            ->setDescription('Update a credential type')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential Type ID')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Credential Type UUID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name')
            ->addOption('class', null, InputOption::VALUE_REQUIRED, 'Class');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $uuid = $input->getOption('uuid');

        if (empty($id) && empty($uuid)) {
            $output->writeln('<error>Missing --id or --uuid</error>');

            return self::FAILURE;
        }

        if (!empty($uuid)) {
            $found = (new CredentialType())->listingQuery()->where(['uuid' => $uuid])->fetch();

            if (!$found) {
                $output->writeln('<error>No credential type found with given UUID</error>');

                return self::FAILURE;
            }

            $id = $found['id'];
        }

        $data = [];

        foreach (['name', 'class'] as $field) {
            $val = $input->getOption($field);

            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        if (empty($data)) {
            $output->writeln('<error>No fields to update</error>');

            return self::FAILURE;
        }

        $credType = new CredentialType((int) $id);
        $credType->updateToSQL($data, ['id' => $id]);

        if ($format === 'json') {
            $output->writeln(json_encode(['updated' => true], \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('Credential type updated successfully');
        }

        return self::SUCCESS;
    }
}

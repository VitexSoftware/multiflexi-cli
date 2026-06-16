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

namespace MultiFlexi\Cli\Command\CredentialPrototype;

use MultiFlexi\CredentialProtoType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends BaseCommand
{
    protected static $defaultName = 'credential-prototype:sync';

    protected function configure(): void
    {
        $this
            ->setName('credential-prototype:sync')
            ->setDescription('Synchronize credential prototypes from filesystem to database')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        \Ease\Functions::loadClassesInNamespace('MultiFlexi\\CredentialProtoType');
        $classesToProcess = \Ease\Functions::classesInNamespace('MultiFlexi\\CredentialProtoType');

        if (empty($classesToProcess)) {
            $output->writeln('<error>No CredentialProtoType Classes found</error>');

            return self::FAILURE;
        }

        $syncStats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0, 'skipped' => 0];
        $output->writeln('<info>Starting synchronization of credential prototypes...</info>');

        foreach ($classesToProcess as $className) {
            if ($className === 'Common') {
                ++$syncStats['skipped'];

                continue;
            }

            $fullClassName = "\\MultiFlexi\\CredentialProtoType\\{$className}";
            ++$syncStats['processed'];

            try {
                if (!class_exists($fullClassName)) {
                    ++$syncStats['skipped'];

                    continue;
                }

                $reflection = new \ReflectionClass($fullClassName);

                if (!$reflection->implementsInterface('\\MultiFlexi\\credentialTypeInterface')) {
                    ++$syncStats['skipped'];

                    continue;
                }

                $instance = new $fullClassName();
                $uuid = $instance->uuid();

                if (empty($uuid)) {
                    ++$syncStats['errors'];

                    continue;
                }

                $credProto = new CredentialProtoType();
                $existing = $credProto->listingQuery()->where(['uuid' => $uuid])->fetch();

                $prototypeData = [
                    'uuid' => $uuid,
                    'code' => $className,
                    'name' => self::getLocalizedString($instance->name()),
                    'description' => self::getLocalizedString($instance->description()),
                    'version' => '1.0',
                    'logo' => $instance->logo(),
                    'url' => '',
                ];

                if ($existing) {
                    $credProto = new CredentialProtoType((int) $existing['id']);
                    $credProto->setData($prototypeData);

                    if ($credProto->save()) {
                        $output->writeln("<info>Updated prototype: {$className}</info>");
                        self::syncPrototypeFields($credProto, $fullClassName, $output);
                        ++$syncStats['updated'];
                    } else {
                        ++$syncStats['errors'];
                    }
                } else {
                    $credProto->setData($prototypeData);

                    if ($credProto->insertToSQL()) {
                        $output->writeln("<info>Created prototype: {$className}</info>");
                        self::syncPrototypeFields($credProto, $fullClassName, $output);
                        ++$syncStats['created'];
                    } else {
                        ++$syncStats['errors'];
                    }
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Error processing {$className}: ".$e->getMessage().'</error>');
                ++$syncStats['errors'];
            }
        }

        if ($format === 'json') {
            $output->writeln(json_encode($syncStats, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln('<info>Synchronization completed:</info>');

            foreach ($syncStats as $k => $v) {
                $output->writeln("  {$k}: {$v}");
            }
        }

        return $syncStats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

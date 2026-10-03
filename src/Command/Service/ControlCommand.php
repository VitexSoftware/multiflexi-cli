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

namespace MultiFlexi\Cli\Command\Service;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ControlCommand extends MultiFlexiCommand
{
    abstract protected function action(): string;

    protected function configureControl(string $name, string $description): void
    {
        $this
            ->setName($name)
            ->setDescription($description)
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'Service name: '.implode(', ', Units::names()),
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        $name = (string) $input->getOption('name');
        $unit = Units::resolve($name);

        if ($unit === null) {
            $msg = 'Unknown service "'.$name.'". Use one of: '.implode(', ', Units::names());
            $format === 'json' ? $this->jsonError($output, $msg) : $output->writeln('<error>'.$msg.'</error>');

            return self::FAILURE;
        }

        $action = $this->action();
        [$ok, $detail] = self::runSystemctl($action, $unit);
        $status = Runner::getServiceStatus($unit);

        if (!$ok) {
            $msg = 'Failed to '.$action.' '.$unit.($detail !== '' ? ': '.$detail : '');
            if ($format === 'json') {
                $this->jsonError($output, $msg);
            } else {
                $output->writeln('<error>'.$msg.'</error>');
            }

            return self::FAILURE;
        }

        if ($format === 'json') {
            $this->jsonSuccess($output, ucfirst($action).' issued', [
                'name' => strtolower($name),
                'unit' => $unit,
                'action' => $action,
                'unit_status' => $status,
            ]);
        } else {
            $output->writeln('<info>'.ucfirst($action).' '.$unit.' — status: '.$status.'</info>');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private static function runSystemctl(string $action, string $unit): array
    {
        $args = [$action, $unit];
        $direct = self::execSystemctl($args);

        if ($direct[0]) {
            return $direct;
        }

        // Retry with passwordless sudo when the CLI is allowed via sudoers.
        return self::execSystemctl($args, true);
    }

    /**
     * @param list<string> $args
     *
     * @return array{0: bool, 1: string}
     */
    private static function execSystemctl(array $args, bool $withSudo = false): array
    {
        $cmd = ($withSudo ? 'sudo -n ' : '').'/usr/bin/systemctl';
        foreach ($args as $arg) {
            $cmd .= ' '.escapeshellarg($arg);
        }
        $cmd .= ' 2>&1';

        $output = [];
        $code = 1;
        exec($cmd, $output, $code);

        return [$code === 0, trim(implode("\n", $output))];
    }
}

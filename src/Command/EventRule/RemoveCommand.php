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

namespace MultiFlexi\Cli\Command\EventRule;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\EventRule;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'event-rule:remove';

    protected function configure(): void
    {
        $this
            ->setName('event-rule:remove')
            ->setDescription('Remove an event rule')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Event Rule ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');

        if (empty($id)) {
            $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        if ((new EventRule())->deleteFromSQL((int) $id)) {
            if ($format === 'json') {
                $output->writeln(json_encode(['removed' => true], \JSON_PRETTY_PRINT));
            } else {
                $output->writeln('Event rule removed successfully');
            }

            return self::SUCCESS;
        }

        $output->writeln('<error>Failed to remove event rule</error>');

        return self::FAILURE;
    }
}

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

namespace MultiFlexi\Cli\Command\Artifact;

use MultiFlexi\Artifact;
use MultiFlexi\Cli\Command\MultiFlexiCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SaveCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'artifact:save';

    protected function configure(): void
    {
        $this
            ->setName('artifact:save')
            ->setDescription('Save artifact content to a file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: text or json', 'text')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Artifact ID')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Destination file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower($input->getOption('format'));
        $id = $input->getOption('id');
        $file = $input->getOption('file');

        if (empty($id)) {
            $format === 'json' ? $this->jsonError($output, 'Missing --id') : $output->writeln('<error>Missing --id</error>');

            return self::FAILURE;
        }

        if (empty($file)) {
            $format === 'json' ? $this->jsonError($output, 'Missing --file') : $output->writeln('<error>Missing --file</error>');

            return self::FAILURE;
        }

        $results = (new Artifact())->listingQuery()->where('id = ?', [(int) $id])->fetchAll();
        $data = !empty($results) ? $results[0] : null;

        if (empty($data)) {
            $format === 'json' ? $this->jsonError($output, "Artifact not found: ID={$id}") : $output->writeln("<error>Artifact not found: ID={$id}</error>");

            return self::FAILURE;
        }

        $artifactContent = $data['artifact'] ?? '';

        if (empty($artifactContent)) {
            $format === 'json' ? $this->jsonError($output, "No content in artifact: ID={$id}") : $output->writeln("<error>No content in artifact: ID={$id}</error>");

            return self::FAILURE;
        }

        $dir = \dirname($file);

        if (!is_dir($dir) && !mkdir($dir, 0o755, true)) {
            $format === 'json' ? $this->jsonError($output, "Failed to create directory: {$dir}") : $output->writeln("<error>Failed to create directory: {$dir}</error>");

            return self::FAILURE;
        }

        if (file_put_contents($file, $artifactContent) === false) {
            $format === 'json' ? $this->jsonError($output, "Failed to save artifact: {$file}") : $output->writeln("<error>Failed to save artifact: {$file}</error>");

            return self::FAILURE;
        }

        $result = ['artifact_id' => $id, 'file' => $file, 'size' => filesize($file), 'saved' => true];

        if (isset($data['filename'])) {
            $result['original_filename'] = $data['filename'];
        }

        if (isset($data['content_type'])) {
            $result['content_type'] = $data['content_type'];
        }

        if ($format === 'json') {
            $output->writeln(json_encode($result, \JSON_PRETTY_PRINT));
        } else {
            $output->writeln("Artifact saved: ID={$id} -> {$file} ({$result['size']} bytes)");
        }

        return self::SUCCESS;
    }
}

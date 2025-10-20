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

use MultiFlexi\Artifact;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of Artifact.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class ArtifactCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'artifact';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    #[\Override]
    public function listing(): array
    {
        $engine = new Artifact();

        return $engine->listingQuery()->select([
            'id',
        ])->fetchAll();
    }

    protected function configure(): void
    {
        $this
            ->setName('artifact')
            ->setDescription('Artifact operations')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->setHelp('This command manages job artifacts')
            ->setDescription('Manage job artifacts')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|save')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Artifact ID')
            ->addOption('job_id', null, InputOption::VALUE_REQUIRED, 'Job ID to filter artifacts')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'File path to save artifact content to')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of fields to display');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        switch ($action) {
            case 'list':
                $artifact = new Artifact();
                $query = $artifact->listingQuery();

                // Filter by job_id if provided
                $jobId = $input->getOption('job_id');

                if ($jobId !== null) {
                    $query->where('job_id = ?', [(int) $jobId]);
                }

                $artifacts = $query->fetchAll();

                if ($format === 'json') {
                    $output->writeln(json_encode($artifacts, \JSON_PRETTY_PRINT));
                } else {
                    if (empty($artifacts)) {
                        $output->writeln(_('No artifacts found'));
                    } else {
                        $output->writeln(self::outputTable($artifacts));
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'get':
                $id = $input->getOption('id');

                if (empty($id)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --id for artifact get');
                    } else {
                        $output->writeln('<error>Missing --id for artifact get</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $artifact = new Artifact();
                $results = $artifact->listingQuery()->where('id = ?', [(int) $id])->fetchAll();
                $data = !empty($results) ? $results[0] : null;

                if (empty($data)) {
                    if ($format === 'json') {
                        $this->jsonError($output, "Artifact not found: ID={$id}");
                    } else {
                        $output->writeln("<error>Artifact not found: ID={$id}</error>");
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $fields = $input->getOption('fields');

                if ($fields) {
                    $fieldsArray = explode(',', $fields);
                    $filteredData = array_filter(
                        $data,
                        static fn ($key) => \in_array($key, $fieldsArray, true),
                        \ARRAY_FILTER_USE_KEY,
                    );

                    if ($format === 'json') {
                        $output->writeln(json_encode($filteredData, \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($filteredData as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                } else {
                    if ($format === 'json') {
                        $output->writeln(json_encode($data, \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($data as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                }

                return MultiFlexiCommand::SUCCESS;
            case 'save':
                $id = $input->getOption('id');
                $file = $input->getOption('file');

                if (empty($id)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --id for artifact save');
                    } else {
                        $output->writeln('<error>Missing --id for artifact save</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                if (empty($file)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --file for artifact save');
                    } else {
                        $output->writeln('<error>Missing --file for artifact save</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $artifact = new Artifact();
                $results = $artifact->listingQuery()->where('id = ?', [(int) $id])->fetchAll();
                $data = !empty($results) ? $results[0] : null;

                if (empty($data)) {
                    if ($format === 'json') {
                        $this->jsonError($output, "Artifact not found: ID={$id}");
                    } else {
                        $output->writeln("<error>Artifact not found: ID={$id}</error>");
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $artifactContent = $data['artifact'] ?? '';

                if (empty($artifactContent)) {
                    if ($format === 'json') {
                        $this->jsonError($output, "No content in artifact: ID={$id}");
                    } else {
                        $output->writeln("<error>No content in artifact: ID={$id}</error>");
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                // Create directory if it doesn't exist
                $dir = \dirname($file);

                if (!is_dir($dir) && !mkdir($dir, 0o755, true)) {
                    if ($format === 'json') {
                        $this->jsonError($output, "Failed to create directory: {$dir}");
                    } else {
                        $output->writeln("<error>Failed to create directory: {$dir}</error>");
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                // Write artifact content to file
                if (file_put_contents($file, $artifactContent) === false) {
                    if ($format === 'json') {
                        $this->jsonError($output, "Failed to save artifact to file: {$file}");
                    } else {
                        $output->writeln("<error>Failed to save artifact to file: {$file}</error>");
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $fileSize = filesize($file);
                $result = [
                    'artifact_id' => $id,
                    'file' => $file,
                    'size' => $fileSize,
                    'saved' => true,
                ];

                if (isset($data['filename'])) {
                    $result['original_filename'] = $data['filename'];
                }

                if (isset($data['content_type'])) {
                    $result['content_type'] = $data['content_type'];
                }

                if ($format === 'json') {
                    $output->writeln(json_encode($result, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln("Artifact saved: ID={$id} -> {$file} ({$fileSize} bytes)");
                }

                return MultiFlexiCommand::SUCCESS;

            default:
                if ($format === 'json') {
                    $this->jsonError($output, "Unknown action: {$action}. Available actions: list, get, save");
                } else {
                    $output->writeln("<error>Unknown action: {$action}. Available actions: list, get, save</error>");
                }

                return MultiFlexiCommand::FAILURE;
        }
    }
}

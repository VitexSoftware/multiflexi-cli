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

use MultiFlexi\Credential;
use MultiFlexi\Company;
use MultiFlexi\CredentialType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of CredentialCommand.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class CredentialCommand extends MultiFlexiCommand
{
    protected static $defaultName = 'credential';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Manage credentials')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'The output format: text or json. Defaults to text.', 'text')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|get|create|update|remove')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Credential ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Credential name')
            ->addOption('company-id', null, InputOption::VALUE_REQUIRED, 'Company ID')
            ->addOption('credential-type-id', null, InputOption::VALUE_REQUIRED, 'Credential Type ID')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of fields to display')
            ->setHelp('This command manages credentials. Use create action to create a new credential based on a credential type.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = strtolower($input->getOption('format'));
        $action = strtolower($input->getArgument('action'));

        // Default action logic: if no id, show list; if id, show record
        if (!\in_array($action, ['create', 'update', 'remove', 'get', 'list'], true)) {
            $id = $input->getOption('id');

            if (empty($id)) {
                $action = 'list';
            } else {
                $action = 'get';
            }
        }

        switch ($action) {
            case 'list':
                $credential = new Credential();
                $credentials = $credential->listingQuery()->fetchAll();

                if ($format === 'json') {
                    $output->writeln(json_encode($credentials, \JSON_PRETTY_PRINT));
                } else {
                    $output->writeln(self::outputTable($credentials));
                }

                return MultiFlexiCommand::SUCCESS;

            case 'get':
                $id = $input->getOption('id');

                if (empty($id)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --id for credential get');
                    } else {
                        $output->writeln('<error>Missing --id for credential get</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $credential = new Credential((int) $id);

                if (empty($credential->getData())) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'No credential found with given ID', 'not found');
                    } else {
                        $output->writeln('<error>No credential found with given ID</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $fields = $input->getOption('fields');

                if ($fields) {
                    $fieldsArray = explode(',', $fields);
                    $filteredData = array_filter(
                        $credential->getData(),
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
                        $output->writeln(json_encode($credential->getData(), \JSON_PRETTY_PRINT));
                    } else {
                        foreach ($credential->getData() as $k => $v) {
                            $output->writeln("{$k}: {$v}");
                        }
                    }
                }

                return MultiFlexiCommand::SUCCESS;

            case 'create':
                $name = $input->getOption('name');
                $companyId = $input->getOption('company-id');
                $credentialTypeId = $input->getOption('credential-type-id');

                // Validate required fields
                if (empty($credentialTypeId)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --credential-type-id for credential create');
                    } else {
                        $output->writeln('<error>Missing --credential-type-id for credential create</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                if (empty($companyId)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --company-id for credential create');
                    } else {
                        $output->writeln('<error>Missing --company-id for credential create</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                // Verify company exists
                $company = new Company((int) $companyId);
                if (empty($company->getData())) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Company with given ID not found');
                    } else {
                        $output->writeln('<error>Company with given ID not found</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                // Verify credential type exists
                $credentialType = new CredentialType((int) $credentialTypeId);
                if (empty($credentialType->getData())) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Credential type with given ID not found');
                    } else {
                        $output->writeln('<error>Credential type with given ID not found</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $data = [
                    'company_id' => (int) $companyId,
                    'credential_type_id' => (int) $credentialTypeId,
                ];

                // If name is provided, use it; otherwise it will be auto-generated from company name
                if (!empty($name)) {
                    $data['name'] = $name;
                }

                try {
                    $credential = new Credential();
                    $credentialId = $credential->insertToSQL($data);

                    if ($format === 'json') {
                        $this->jsonSuccess($output, 'Credential created successfully', [
                            'credential_id' => $credentialId,
                            'created' => true,
                        ]);
                    } else {
                        $output->writeln("<info>Credential created successfully with ID: {$credentialId}</info>");
                    }

                    return MultiFlexiCommand::SUCCESS;
                } catch (\Exception $e) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Failed to create credential: ' . $e->getMessage());
                    } else {
                        $output->writeln('<error>Failed to create credential: ' . $e->getMessage() . '</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

            case 'update':
                $id = $input->getOption('id');
                $name = $input->getOption('name');
                $companyId = $input->getOption('company-id');
                $credentialTypeId = $input->getOption('credential-type-id');

                if (empty($id)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --id for credential update');
                    } else {
                        $output->writeln('<error>Missing --id for credential update</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $credential = new Credential((int) $id);
                if (empty($credential->getData())) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'No credential found with given ID', 'not found');
                    } else {
                        $output->writeln('<error>No credential found with given ID</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $data = [];

                if (!empty($name)) {
                    $data['name'] = $name;
                }

                if (!empty($companyId)) {
                    // Verify company exists
                    $company = new Company((int) $companyId);
                    if (empty($company->getData())) {
                        if ($format === 'json') {
                            $this->jsonError($output, 'Company with given ID not found');
                        } else {
                            $output->writeln('<error>Company with given ID not found</error>');
                        }

                        return MultiFlexiCommand::FAILURE;
                    }
                    $data['company_id'] = (int) $companyId;
                }

                if (!empty($credentialTypeId)) {
                    // Verify credential type exists
                    $credentialType = new CredentialType((int) $credentialTypeId);
                    if (empty($credentialType->getData())) {
                        if ($format === 'json') {
                            $this->jsonError($output, 'Credential type with given ID not found');
                        } else {
                            $output->writeln('<error>Credential type with given ID not found</error>');
                        }

                        return MultiFlexiCommand::FAILURE;
                    }
                    $data['credential_type_id'] = (int) $credentialTypeId;
                }

                if (empty($data)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'No fields to update');
                    } else {
                        $output->writeln('<error>No fields to update</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                try {
                    $credential->updateToSQL($data, ['id' => $id]);

                    if ($format === 'json') {
                        $this->jsonSuccess($output, 'Credential updated successfully', [
                            'credential_id' => (int) $id,
                            'updated' => true,
                        ]);
                    } else {
                        $output->writeln("<info>Credential updated successfully</info>");
                    }

                    return MultiFlexiCommand::SUCCESS;
                } catch (\Exception $e) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Failed to update credential: ' . $e->getMessage());
                    } else {
                        $output->writeln('<error>Failed to update credential: ' . $e->getMessage() . '</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

            case 'remove':
                $id = $input->getOption('id');

                if (empty($id)) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Missing --id for credential remove');
                    } else {
                        $output->writeln('<error>Missing --id for credential remove</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                $credential = new Credential((int) $id);
                if (empty($credential->getData())) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'No credential found with given ID', 'not found');
                    } else {
                        $output->writeln('<error>No credential found with given ID</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

                try {
                    $credential->deleteFromSQL();

                    if ($format === 'json') {
                        $this->jsonSuccess($output, 'Credential removed successfully', [
                            'credential_id' => (int) $id,
                            'removed' => true,
                        ]);
                    } else {
                        $output->writeln("<info>Credential removed successfully</info>");
                    }

                    return MultiFlexiCommand::SUCCESS;
                } catch (\Exception $e) {
                    if ($format === 'json') {
                        $this->jsonError($output, 'Failed to remove credential: ' . $e->getMessage());
                    } else {
                        $output->writeln('<error>Failed to remove credential: ' . $e->getMessage() . '</error>');
                    }

                    return MultiFlexiCommand::FAILURE;
                }

            default:
                if ($format === 'json') {
                    $this->jsonError($output, "Unknown action: {$action}");
                } else {
                    $output->writeln("<error>Unknown action: {$action}</error>");
                }

                return MultiFlexiCommand::FAILURE;
        }
    }
}
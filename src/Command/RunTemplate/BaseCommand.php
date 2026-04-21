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

namespace MultiFlexi\Cli\Command\RunTemplate;

use MultiFlexi\Cli\Command\MultiFlexiCommand;
use MultiFlexi\ConfigField;
use MultiFlexi\ConfigFields;
use MultiFlexi\RunTemplate;
use Symfony\Component\Console\Input\InputInterface;

abstract class BaseCommand extends MultiFlexiCommand
{
    protected function isValidCronExpression(string $expression): bool
    {
        $parts = preg_split('/\s+/', trim($expression));

        if (\count($parts) !== 5) {
            return false;
        }

        foreach ($parts as $field) {
            if (!preg_match('/^[\d\*,\/-]+$/', $field)) {
                return false;
            }
        }

        return true;
    }

    protected function parseConfigOptions(InputInterface $input): array
    {
        $configs = $input->getOption('config') ?? [];
        $result = [];

        foreach ($configs as $item) {
            if (str_contains($item, '=')) {
                [$key, $value] = explode('=', $item, 2);
                $result[$key] = $value;
            }
        }

        return $result;
    }

    protected function buildOverridedEnv(InputInterface $input): ConfigFields
    {
        $overrideEnv = $this->parseConfigOptions($input);
        $overridedEnv = new ConfigFields('CommandlineOverride');

        foreach ($overrideEnv as $key => $value) {
            $overridedEnv->addField(new ConfigField($key, 'string', $key, '', '', $value));
        }

        return $overridedEnv;
    }

    protected function setRuntemplateConfig(RunTemplate $rt, ConfigFields $overrideEnv): void
    {
        if ($overrideEnv->getEnvArray()) {
            if ($rt->setEnvironment($overrideEnv->getEnvArray())) {
                $rt->addStatusMessage(_('Config fields Saved'), 'success');
                $setupCommand = $rt->getApplication()->getDataValue('setup');

                if (!empty($setupCommand)) {
                    $appEnvironment = $rt->getEnvironment()->getEnvArray();
                    $process = new \Symfony\Component\Process\Process(
                        explode(' ', $setupCommand),
                        null,
                        $appEnvironment,
                        null,
                        32767,
                    );
                    $result = $process->run();
                    $outputText = $process->getOutput();
                    $errorText = $process->getErrorOutput();

                    if ($result === 0) {
                        $rt->addStatusMessage(_('Setup command executed successfully:'), 'success');

                        if ($outputText) {
                            $rt->addStatusMessage($outputText, 'info');
                        }
                    } else {
                        $rt->addStatusMessage(_('Setup command failed:'), 'error');

                        if ($errorText) {
                            $rt->addStatusMessage($errorText, 'error');
                        }
                    }
                }
            } else {
                $rt->addStatusMessage(_('Error saving Config fields'), 'error');
            }
        }
    }
}

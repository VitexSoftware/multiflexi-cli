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

namespace MultiFlexi\Cli;

/**
 * Headless queue listing query for `queue`/`queue:list`/`queue:overview`.
 *
 * Formerly `MultiFlexi\ScheduleLister` (multiflexi-core), which also carried
 * DataTables column/HTML rendering for the web UI. That class moved to
 * multiflexi-web5; this CLI only ever used its listingQuery() join, so that
 * part lives here instead of pulling in a web-UI dependency.
 */
class ScheduleQuery extends \MultiFlexi\DBEngine
{
    public function __construct($identifier = null, $options = [])
    {
        $this->myTable = 'schedule';
        $this->nameColumn = '';
        parent::__construct($identifier, $options);
    }

    public function listingQuery(): \Envms\FluentPDO\Queries\Select
    {
        return parent::listingQuery()
            ->leftJoin('job ON job.id = schedule.job')->select(['job.schedule_type'])
            ->leftJoin('user ON user.id = job.launched_by')
            ->leftJoin('runtemplate ON runtemplate.id = job.runtemplate_id')->select(['runtemplate.name AS runtemplate_name', 'runtemplate.id AS runtemplate_id'])
            ->leftJoin('apps ON apps.id = runtemplate.app_id')->select(['apps.name AS app_name', 'apps.id AS app_id'])
            ->leftJoin('company ON company.id = runtemplate.company_id')->select(['company.name AS company_name', 'company.id AS company_id']);
    }
}

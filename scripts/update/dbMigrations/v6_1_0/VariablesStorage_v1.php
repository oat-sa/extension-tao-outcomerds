<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoOutcomeRds\scripts\update\dbMigrations\v6_1_0;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\RdsResultStorage;

/**
 * Class VariablesStorage_v1
 *
 * NOTE! Do not change this file. If you need to change schema of storage create new version of this class.
 *
 * @package oat\taoOutcomeRds\scripts\update\dbMigrations
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class VariablesStorage_v1 extends AbstractAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        /** @var RdsResultStorage $service */
        $service = $this->getServiceManager()->get(RdsResultStorage::SERVICE_ID);
        $persistence = $service->getPersistence();
        $this->alterTable($persistence);

        return \common_report_Report::createSuccess('RDS variables storage was successfully migrated to v1');
    }

    /**
     * Update table in database
     * @param \common_persistence_SqlPersistence $persistence
     */
    protected function alterTable(\common_persistence_SqlPersistence $persistence)
    {
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $persistence->getSchemaManager();

        /** @var Schema $schema */
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $table = $schema->getTable(RdsResultStorage::VARIABLES_TABLENAME);
            $table->addUniqueIndex([RdsResultStorage::VARIABLE_HASH], RdsResultStorage::UNIQUE_VARIABLE_INDEX);
        } catch (SchemaException $e) {
            \common_Logger::i('Database schema of RdsResultStorage service is already up to date.');
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);

        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}

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
 * Copyright (c) 2014-2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\taoOutcomeRds\scripts\SchemaChange;

use common_Logger as Logger;
use common_persistence_Persistence as Persistence;
use common_report_Report as Report;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\extension\AbstractAction;

abstract class SchemaChangeAction extends AbstractAction
{
    const ACTION_NAME = '';

    /** @var Persistence */
    protected $persistence;

    /**
     * @param $params
     *
     * @return Report
     */
    public function __invoke($params)
    {
        $this->persistence = $this->beforeChange();

        $oldSchema = $this->readSchema();
        $newSchema = clone $oldSchema;
        try {
            $report = $this->changeSchema($newSchema);
        } catch (SchemaException $exception) {
            return Report::createFailure($exception->getMessage());
        }

        try {
            $this->writeSchema($oldSchema, $newSchema);
        } catch (DBALException $e) {
            Logger::w($e->getMessage());
            return Report::createFailure(__('Something went wrong during ' . $this->getActionName()));
        }

        $this->afterChange();

        return $report;
    }

    /**
     * Actions to be done before schema change.
     * This must return the persistence
     *
     * @return Persistence
     */
    protected function beforeChange()
    {
        return $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID)->getPersistenceById('default');
    }

    /**
     * Reads schema from persistence.
     *
     * @return Schema
     */
    private function readSchema()
    {
        /** @var \common_persistence_sql_dbal_SchemaManager $schemaManager */
        $schemaManager = $this->persistence->getDriver()->getSchemaManager();
        return $schemaManager->createSchema();
    }

    /**
     * Performs the schema changes.
     *
     * @param Schema $schema
     *
     * @throws SchemaChangeException
     */
    abstract protected function changeSchema(Schema $schema);

    /**
     * Write new schema to persistence.
     *
     * @param Schema $oldSchema
     * @param Schema $newSchema
     *
     * @throws DBALException
     */
    private function writeSchema(Schema $oldSchema, Schema $newSchema)
    {
        $queries = $this->persistence->getPlatform()->getMigrateSchemaSql($oldSchema, $newSchema);
        foreach ($queries as $query) {
            $this->persistence->exec($query);
        }
    }

    /**
     * Actions to be done after successful schema change.
     */
    protected function afterChange()
    {
    }

    protected function getActionName()
    {
        return static::ACTION_NAME;
    }
}

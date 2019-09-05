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
namespace oat\taoOutcomeRds\scripts\install;

use common_Logger;
use common_persistence_SqlPersistence as Persistence;
use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\CompatibleSchemaInterface;
use oat\taoOutcomeRds\model\RdsResultStorage;
use Doctrine\DBAL\Schema\SchemaException;

class createTables extends AbstractAction
{
    public function __invoke($params)
    {
        /** @var RdsResultStorage $rdsResultStorage */
        $rdsResultStorage = $this->getServiceManager()->get(RdsResultStorage::SERVICE_ID);
        $persistence = $rdsResultStorage->getPersistence();
        $compatibleSchema = $rdsResultStorage->getCompatibleSchema();

        $this->generateTables($persistence, $compatibleSchema);
    }

    /**
     * @param \common_persistence_SqlPersistence $persistence
     * @param CompatibleSchemaInterface $compatibleSchema
     */
    public function generateTables(Persistence $persistence, CompatibleSchemaInterface $compatibleSchema)
    {
        /** @var \common_persistence_sql_dbal_SchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $tableResults = $schema->createtable(RdsResultStorage::RESULTS_TABLENAME);
            $tableResults->addOption('engine', 'MyISAM');
            $tableResults->addColumn(RdsResultStorage::RESULTS_TABLE_ID, 'string', ['length' => 255]);
            $tableResults->addColumn(RdsResultStorage::TEST_TAKER_COLUMN, 'string', ['notnull' => false, 'length' => 255]);
            $tableResults->addColumn(RdsResultStorage::DELIVERY_COLUMN, 'string', ['notnull' => false, 'length' => 255]);
            $tableResults->setPrimaryKey([RdsResultStorage::RESULTS_TABLE_ID]);

            $tableVariables = $schema->createtable(RdsResultStorage::VARIABLES_TABLENAME);
            $tableVariables->addOption('engine', 'MyISAM');

            $compatibleSchema->getFieldDefinitionsForTableVariables($tableVariables);

            $tableVariables->setPrimaryKey([RdsResultStorage::VARIABLES_TABLE_ID]);
            $tableVariables->addForeignKeyConstraint(
                $tableResults,
                [RdsResultStorage::VARIABLES_FK_COLUMN],
                [RdsResultStorage::RESULTS_TABLE_ID],
                [],
                RdsResultStorage::VARIABLES_FK_NAME
            );
            $tableVariables->addIndex([RdsResultStorage::CALL_ID_ITEM_COLUMN], RdsResultStorage::CALL_ID_ITEM_INDEX);
            $tableVariables->addIndex([RdsResultStorage::CALL_ID_TEST_COLUMN], RdsResultStorage::CALL_ID_TEST_INDEX);
            $tableVariables->addUniqueIndex([
                RdsResultStorage::VARIABLE_HASH
            ], RdsResultStorage::UNIQUE_VARIABLE_INDEX);

        } catch (SchemaException $e) {
            common_Logger::i('Database Schema already up to date.');
        }

        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}

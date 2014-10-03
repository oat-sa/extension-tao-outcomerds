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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

use oat\taoOutcomeRds\models\classes\RdsResultStorage;

$persistence = common_persistence_Manager::getPersistence('default');

$schemaManager = $persistence->getDriver()->getSchemaManager();
$schema = $schemaManager->createSchema();
$fromSchema = clone $schema;
$tableResults = $schema->createtable(RdsResultStorage::RESULTS_TABLENAME);
$tableVariables = $schema->createtable(RdsResultStorage::VARIABLES_TABLENAME);
$tableKvResults = $schema->createtable(RdsResultStorage::RESULT_KEY_VALUE_TABLE_NAME);

$tableResults->addColumn(RdsResultStorage::RESULTS_TABLE_ID,"string",array("notnull" => false, "length" => 255));
$tableResults->addColumn(RdsResultStorage::TEST_TAKER_COLUMN,"string",array("length" => 255));
$tableResults->addColumn(RdsResultStorage::DELIVERY_COLUMN,"string",array("length" => 255));
$tableResults->setPrimaryKey(array(RdsResultStorage::RESULTS_TABLE_ID));

$tableVariables->addColumn(RdsResultStorage::VARIABLES_TABLE_ID,"integer",array("autoincrement" => true));
$tableVariables->addColumn(RdsResultStorage::CALL_ID_TEST_COLUMN,"string",array("length" => 255));
$tableVariables->addColumn(RdsResultStorage::CALL_ID_ITEM_COLUMN,"string",array("length" => 255));
$tableVariables->addColumn(RdsResultStorage::TEST_COLUMN,"string",array("notnull" => false,"length" => 255));
$tableVariables->addColumn(RdsResultStorage::ITEM_COLUMN,"string",array("notnull" => false,"length" => 255));
$tableVariables->addColumn(RdsResultStorage::VARIABLE_IDENTIFIER,"string",array("notnull" => false,"length" => 255));
$tableVariables->addColumn(RdsResultStorage::VARIABLE_CLASS,"string",array("notnull" => false,"length" => 255));
$tableVariables->addColumn(RdsResultStorage::VARIABLES_FK_COLUMN,"string",array("length" => 255));
$tableVariables->setPrimaryKey(array(RdsResultStorage::VARIABLES_TABLE_ID));
$tableVariables->addForeignKeyConstraint($tableResults,
    array(RdsResultStorage::VARIABLES_FK_COLUMN),
    array(RdsResultStorage::RESULTS_TABLE_ID),
    array(),
    RdsResultStorage::VARIABLES_FK_NAME);

$tableKvResults->addColumn(RdsResultStorage::RESULTSKV_FK_COLUMN,"integer",array("notnull" => false));
$tableKvResults->addColumn(RdsResultStorage::KEY_COLUMN,"string",array("notnull" => false,"length" => 255));
$tableKvResults->addColumn(RdsResultStorage::VALUE_COLUMN,"string",array("notnull" => false,"length" => 255));
$tableKvResults->setPrimaryKey(array(
        RdsResultStorage::RESULTSKV_FK_COLUMN,
        RdsResultStorage::KEY_COLUMN));
$tableKvResults->addForeignKeyConstraint($tableVariables,
    array(RdsResultStorage::RESULTSKV_FK_COLUMN),
    array(RdsResultStorage::VARIABLES_TABLE_ID),
    array(),
    RdsResultStorage::RESULTSKV_FK_NAME);


$queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
foreach ($queries as $query){
    $persistence->exec($query);
}
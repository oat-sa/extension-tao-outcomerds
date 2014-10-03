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


$persistence = common_persistence_Manager::getPersistence('default');
$schema = $persistence->getDriver()->getSchemaManager()->createSchema();
$fromSchema = clone $schema;

/**
 * @throws PDOException 
 */
$tableResultsKv = $schema->dropTable(taoOutcomeRds_models_classes_RdsResultStorage::RESULT_KEY_VALUE_TABLE_NAME);
$tableVariables = $schema->dropTable(taoOutcomeRds_models_classes_RdsResultStorage::VARIABLES_TABLENAME);
$tableResults = $schema->dropTable(taoOutcomeRds_models_classes_RdsResultStorage::RESULTS_TABLENAME);
$queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
foreach ($queries as $query){
    $persistence->exec($query);
}

// remove statement entries for object = taoOutcomeRds_models_classes_RdsResultStorage
$storage = new core_kernel_classes_Resource('http://www.tao.lu/Ontologies/taoOutcomeRds.rdf#RdsResultStorage');
$storage->delete();
$model = new core_kernel_classes_Resource('http://www.tao.lu/Ontologies/taoOutcomeRds.rdf#RdsResultStorageModel');
$model->delete();

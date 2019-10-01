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
 * Copyright (c) 2014-2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\taoOutcomeRds\scripts\install;

use common_report_Report as Report;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use oat\taoOutcomeRds\scripts\SchemaChange\ResultStorageSchemaChangeAction;

class CreateTables extends ResultStorageSchemaChangeAction
{
    const ACTION_NAME = 'table creation';

    /**
     * @inheritdoc
     * Creates tables in the new schema.
     */
    protected function changeSchema(Schema $schema)
    {
        $resultsTable = $this->getOrCreateTable($schema, $this->resultStorage::RESULTS_TABLENAME, 'createResultsTable');
        $variablesTable = $this->getOrCreateTable($schema, $this->resultStorage::VARIABLES_TABLENAME, 'createVariablesTable');

        try {
            $this->resultStorage->createTableConstraints($variablesTable, $resultsTable);
        } catch (SchemaException $e) {
            return Report::createFailure('Database Schema already up to date.');
        }

        return Report::createSuccess(__('2 tables created.'));
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param string $creationMethodName
     *
     * @return Table
     */
    private function getOrCreateTable(Schema $schema, $tableName, $creationMethodName)
    {
        try {
            $table = $schema->getTable($tableName);
        } catch (SchemaException $exception) {
            try {
                $table = $this->resultStorage->$creationMethodName($schema);
            } catch (SchemaException $exception) {
                // Table already exists. How can this happen, since we're doing this because
                // it doesn't exist in the first place?
            }
        }

        return $table;
    }
}

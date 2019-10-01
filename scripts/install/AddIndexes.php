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
 * Copyright (c) 2017-2019 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoOutcomeRds\scripts\install;

use common_report_Report as Report;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use oat\taoOutcomeRds\scripts\SchemaChange\ResultStorageSchemaChangeAction;
use oat\taoOutcomeRds\scripts\SchemaChange\SchemaChangeException;

class AddIndexes extends ResultStorageSchemaChangeAction
{
    const ACTION_NAME = 'index addition';

    /**
     * @inheritdoc
     * Creates the new tables.
     * @throws SchemaChangeException when the variable table doesn't exist.
     */
    protected function changeSchema(Schema $schema)
    {
        try {
            $tableVariables = $schema->getTable($this->resultStorage::VARIABLES_TABLENAME);
        } catch (SchemaException $exception) {
            throw new SchemaChangeException(sprintf(__('The table %s doesn\'t exist', $this->resultStorage::VARIABLES_TABLENAME)));
        }

        $addedIndexes = 0;
        $addedIndexes += $this->createIndex($tableVariables, $this->resultStorage::CALL_ID_ITEM_INDEX, [$this->resultStorage::CALL_ID_ITEM_COLUMN]);
        $addedIndexes += $this->createIndex($tableVariables, $this->resultStorage::CALL_ID_TEST_INDEX, [$this->resultStorage::CALL_ID_TEST_COLUMN]);

        if ($addedIndexes) {
            return Report::createSuccess(__('Successfully added %s indexes', $addedIndexes));
        }

        return Report::createInfo(__('No indexes to add'));
    }

    /**
     * @param Table  $table
     * @param string $indexName
     * @param array  $fields
     *
     * @return int number of indexes created
     */
    private function createIndex(Table $table, $indexName, array $fields)
    {
        if ($table->hasIndex($indexName)) {
            return 0;
        }
        
        $table->addIndex($fields, $indexName);
        return 1;
    }
}

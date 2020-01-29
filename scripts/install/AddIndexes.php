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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoOutcomeRds\scripts\install;

use Doctrine\DBAL\DBALException;
use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;

class AddIndexes extends AbstractAction
{
    /**
     *
     * @param $params
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        /** @var AbstractRdsResultStorage $resultStorage */
        $resultStorage = $this->getServiceLocator()->get(AbstractRdsResultStorage::SERVICE_ID);
        $persistence = $resultStorage->getPersistence();

        $schema = $persistence->getSchemaManager()->createSchema();
        $fromSchema = clone $schema;

        if ($schema->hasTable(AbstractRdsResultStorage::VARIABLES_TABLENAME)) {
            try {
                $tableVariables = $schema->getTable(AbstractRdsResultStorage::VARIABLES_TABLENAME);
                $i = 0;
                if (!$tableVariables->hasIndex(AbstractRdsResultStorage::CALL_ID_ITEM_INDEX)) {
                    $tableVariables->addIndex([AbstractRdsResultStorage::CALL_ID_ITEM_COLUMN], AbstractRdsResultStorage::CALL_ID_ITEM_INDEX);
                    $i++;
                }

                if (!$tableVariables->hasIndex(AbstractRdsResultStorage::CALL_ID_TEST_INDEX)) {
                    $tableVariables->addIndex([AbstractRdsResultStorage::CALL_ID_TEST_COLUMN], AbstractRdsResultStorage::CALL_ID_TEST_INDEX);
                    $i++;
                }

                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);


                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            } catch (DBALException $e) {
                \common_Logger::w($e->getMessage());
                return \common_report_Report::createFailure(__('Something went wrong during indexes addition'));
            }

            if ($i === 0) {
                return \common_report_Report::createInfo(__('No indexes to add'));
            } else {
                return \common_report_Report::createSuccess(__('Successfully added %s indexes', $i));
            }
        }

        return \common_report_Report::createFailure(__('The table %s doesn\'t exist', AbstractRdsResultStorage::VARIABLES_TABLENAME));
    }
}

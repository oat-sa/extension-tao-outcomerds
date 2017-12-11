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

use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\RdsResultStorage;

class AddIndexes extends AbstractAction
{
    /**
     *
     * @param $params
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        $persistence = $this->getServiceManager()->get(RdsResultStorage::SERVICE_ID)->getPersistence();

        $schema = $persistence->getDriver()->getSchemaManager()->createSchema();
        $fromSchema = clone $schema;


        if($schema->hasTable(RdsResultStorage::VARIABLES_TABLENAME)){
            try{
            $tableVariables = $schema->getTable(RdsResultStorage::VARIABLES_TABLENAME);
            $i = 0;
            if(!$tableVariables->hasIndex(RdsResultStorage::CALL_ID_ITEM_INDEX)){
                $tableVariables->addIndex(array(RdsResultStorage::CALL_ID_ITEM_COLUMN), RdsResultStorage::CALL_ID_ITEM_INDEX);
                $i++;
            }

            if(!$tableVariables->hasIndex(RdsResultStorage::CALL_ID_TEST_INDEX)){
                $tableVariables->addIndex(array(RdsResultStorage::CALL_ID_TEST_COLUMN), RdsResultStorage::CALL_ID_TEST_INDEX);
                $i++;
            }

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);


            foreach ($queries as $query) {
                $persistence->exec($query);
            }

            } catch(\PDOException $e){
                \common_Logger::w($e->getMessage());
                return \common_report_Report::createFailure(__('Something went wrong during indexes addition'));
            }

            if($i === 0){
                return \common_report_Report::createInfo(__('No indexes to add'));
            } else {
                return \common_report_Report::createSuccess(__('Successfully added %s indexes', $i));
            }
        }

        return \common_report_Report::createFailure(__('The table %s doesn\'t exist', RdsResultStorage::VARIABLES_TABLENAME));
    }
}
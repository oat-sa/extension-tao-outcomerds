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
use Doctrine\DBAL\Types\Types;
use Laminas\ServiceManager\ServiceLocatorAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\reporting\Report;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;
use Throwable;

class AddExternallyGradedColumnToVariablesStorage extends AbstractAction
{
    use LoggerAwareTrait;
    use ServiceLocatorAwareTrait;

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

        try {
            $schema->getTable(AbstractRdsResultStorage::VARIABLES_TABLENAME)
                ->addColumn(AbstractRdsResultStorage::IS_EXTERNALLY_GRADED, Types::BOOLEAN, ['default' => false]);

            $persistence->getPlatForm()->migrateSchema($fromSchema, $schema);
        } catch (Throwable $e) {
            $this->getLogger()->error($e);

            return Report::createError($e->getMessage());
        }

        return Report::createSuccess('Added new database column "externallyGraded" to table "variables_storage"');
    }
}

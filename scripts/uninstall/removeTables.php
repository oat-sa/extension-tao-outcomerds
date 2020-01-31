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

namespace oat\taoOutcomeRds\scripts\uninstall;

use common_persistence_SqlPersistence as SqlPersistence;
use Doctrine\DBAL\DBALException;
use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;
use oat\generis\model\data\ModelManager;
use oat\tao\model\extension\ExtensionModel;

class removeTables extends AbstractAction
{

    /**
     * @param $params
     * @throws \common_exception_InconsistentData
     * @throws \common_ext_ExtensionException
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws DBALException
     */
    public function __invoke($params)
    {
        /** @var AbstractRdsResultStorage $resultStorage */
        $resultStorage = $this->getServiceManager()->get(AbstractRdsResultStorage::SERVICE_ID);
        $persistence = $resultStorage->getPersistence();

        $schema = $persistence->getSchemaManager()->createSchema();
        $fromSchema = clone $schema;

        $tableVariables = $schema->dropTable(AbstractRdsResultStorage::VARIABLES_TABLENAME);
        $tableResults = $schema->dropTable(AbstractRdsResultStorage::RESULTS_TABLENAME);
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        // remove statement entries for this extension
        $model = new ExtensionModel(\common_ext_ExtensionsManager::singleton()->getExtensionById('taoOutcomeRds'));
        $modelRdf = ModelManager::getModel()->getRdfInterface();
        foreach ($model as $triple) {
            $modelRdf->remove($triple);
        }
    }
}

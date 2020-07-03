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

use common_Logger;
use common_persistence_sql_dbal_SchemaManager as SchemaManager;
use common_persistence_SqlPersistence as Persistence;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;
use oat\generis\persistence\PersistenceManager;

class CreateTables extends AbstractAction
{
    public function __invoke($params)
    {
        $persistenceManager = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID);
        /** @var AbstractRdsResultStorage $resultStorage */
        $resultStorage = $this->getServiceLocator()->get(AbstractRdsResultStorage::SERVICE_ID);
        $persistenceManager->applySchemaProvider($resultStorage);
    }
}

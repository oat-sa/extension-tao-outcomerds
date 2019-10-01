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

namespace oat\taoOutcomeRds\scripts\SchemaChange;

use common_Logger as Logger;
use common_persistence_Persistence as Persistence;
use common_report_Report as Report;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;

abstract class ResultStorageSchemaChangeAction extends SchemaChangeAction
{
    /** @var AbstractRdsResultStorage */
    protected $resultStorage;

    /**
     * @inheritdoc
     * Gets the persistence.
     */
    protected function beforeChange()
    {
        $this->resultStorage = $this->getServiceLocator()->get(AbstractRdsResultStorage::SERVICE_ID);
        return $this->resultStorage->getPersistence();
    }
}

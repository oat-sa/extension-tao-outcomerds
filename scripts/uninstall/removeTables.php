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

use common_ext_ExtensionsManager as ExtensionsManager;
use Doctrine\DBAL\Schema\Schema;
use oat\generis\model\data\ModelManager;
use oat\tao\model\extension\ExtensionModel;
use oat\taoOutcomeRds\scripts\SchemaChange\ResultStorageSchemaChangeAction;

class removeTables extends ResultStorageSchemaChangeAction
{
    const ACTION_NAME = 'table removal';

    /**
     * @inheritdoc
     * Removes tables in the new schema.
     */
    protected function changeSchema(Schema $schema)
    {
        $schema->dropTable($this->resultStorage::VARIABLES_TABLENAME);
        $schema->dropTable($this->resultStorage::RESULTS_TABLENAME);
    }

    /**
     * @inheritdoc
     * Removes statement entries for this extension.
     */
    protected function afterChange()
    {
        /** @var ExtensionsManager $extensionManager */
        $extensionManager = $this->getServiceLocator()->get(ExtensionsManager::SERVICE_ID);
        $model = new ExtensionModel($extensionManager->getExtensionById('taoOutcomeRds'));

        $modelRdf = ModelManager::getModel()->getRdfInterface();
        foreach ($model as $triple) {
            $modelRdf->remove($triple);
        }
    }
}

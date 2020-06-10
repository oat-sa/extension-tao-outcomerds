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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */
declare(strict_types=1);

namespace oat\taoOutcomeRds\scripts\uninstall;

use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\DummyFeatureManager;
use common_persistence_sql_dbal_SchemaManager as SchemaManager;

/**
 * Class RemoveDummyFeatureTables.
 *
 * An invokable Action that is triggered at uninstall time to remove
 * database tables related to the Dummy Feature.
 *
 * @package oat\taoOutcomeRds\scripts\uninstall
 */
class RemoveDummyFeatureTables extends AbstractAction
{
    /**
     * @param array $params
     */
    public function __invoke($params): void
    {
        /*
         * At installation time, the DummyFeatureManager Service is already registered thanks
         * to its default configuration file.
         */
        /** @var DummyFeatureManager $dummyFeatureManager */
        $dummyFeatureManager = $this->getServiceLocator()->get(DummyFeatureManager::SERVICE_ID);
        $persistence = $dummyFeatureManager->getPersistence();

        /** @var SchemaManager $schemaManager */
        $schema = $persistence->getSchemaManager()->createSchema();
        $fromSchema = clone $schema;

        $dummyFeatureManager->downgradeDatabase($schema);

        /*
         * Contrary to the Migration classes we setup up for the Update Process,
         * the schema migration has to be performed manually.
         */
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}

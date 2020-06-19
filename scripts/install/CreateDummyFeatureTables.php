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

namespace oat\taoOutcomeRds\scripts\install;

use Doctrine\DBAL\DBALException;
use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\DummyFeatureManager;

/**
 * Class CreateDummyFeatureTables.
 *
 * An invokable Action that is triggered at installation time to setup
 * database for our dummy feature.
 *
 * @package oat\taoOutcomeRds\scripts\install
 */
class CreateDummyFeatureTables extends AbstractAction
{
    /**
     * @param array $params
     * @throws DBALException
     */
    public function __invoke($params): void
    {
        /*
         * At installation time, the DummyFeatureManager Service is already registered thanks
         * to its config/default configuration file.
         */
        /** @var DummyFeatureManager $dummyFeatureManager */
        $dummyFeatureManager = $this->getServiceLocator()->get(DummyFeatureManager::SERVICE_ID);
        $dummyFeatureManager->upgradeDatabase();

        $this->getLogger()->debug('Installation Schema upgrade done.');
    }
}

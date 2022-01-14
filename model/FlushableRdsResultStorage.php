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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoOutcomeRds\model;

use oat\taoResultServer\models\Exceptions\DuplicateVariableException;

class FlushableRdsResultStorage extends RdsResultStorage
{
    private $storageState = [];

    public function storeItemVariables($deliveryResultIdentifier, $test, $item, array $itemVariables, $callIdItem)
    {
        foreach ($itemVariables as $itemVariable) {
            $this->storageState[] = $this->prepareItemVariableData(
                $deliveryResultIdentifier,
                $test,
                $item,
                $itemVariable,
                $callIdItem
            );
        }
    }

    public function storeTestVariables($deliveryResultIdentifier, $test, array $testVariables, $callIdTest)
    {
        foreach ($testVariables as $testVariable) {
            $this->storageState[] = $this->prepareTestVariableData(
                $deliveryResultIdentifier,
                $test,
                $testVariable,
                $callIdTest
            );
        }
    }

    /**
     * @throws DuplicateVariableException
     */
    public function flush(): void
    {
        try {
            $this->insertMultiple($this->storageState);
        } finally {
            $this->storageState = [];
        }
    }
}

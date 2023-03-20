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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

declare(strict_types=1);

namespace oat\taoOutcomeRds\test\unit\model;

use common_persistence_SqlPersistence;
use oat\generis\persistence\PersistenceManager;
use oat\generis\test\ServiceManagerMockTrait;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use taoResultServer_models_classes_Variable;

class AbstractRdsResultStorageTest extends TestCase
{
    use ServiceManagerMockTrait;

    /** @var AbstractRdsResultStorage|MockObject */
    protected AbstractRdsResultStorage $instance;

    /** @var common_persistence_SqlPersistence|MockObject */
    protected common_persistence_SqlPersistence $persistence;

    public function setUp(): void
    {
        $this->persistenceManager = $this->createMock(PersistenceManager::class);
        $this->persistence = $this->createMock(common_persistence_SqlPersistence::class);

        $this->persistenceManager
            ->method('getPersistenceById')
            ->willReturn($this->persistence);

        $this->instance = $this->getMockForAbstractClass(AbstractRdsResultStorage::class);
        $this->instance->setServiceLocator(
            $this->getServiceManagerMock(
                [
                    PersistenceManager::SERVICE_ID => $this->persistenceManager,
                ]
            )
        );
    }

    public function testReplaceItemVariables(): void
    {
        $variable = $this->createMock(taoResultServer_models_classes_Variable::class);

        $this->persistence
            ->expects($this->once())
            ->method('updateMultiple')
            ->with(
                AbstractRdsResultStorage::VARIABLES_TABLENAME,
                [
                    [
                        'conditions' => [
                            'variable_id' => 777,
                        ],
                        'updateValues' => [
                            'item' => 'itemUri',
                            'call_id_item' => 'callItemId',
                        ]
                    ]
                ]
            );

        $this->instance->replaceItemVariables(
            'deliveryExecutionId',
            'testUri',
            'itemUri',
            'callItemId',
            [
                777 => $variable
            ]
        );
    }

    public function testReplaceTestVariables(): void
    {
        $variable = $this->createMock(taoResultServer_models_classes_Variable::class);

        $this->persistence
            ->expects($this->once())
            ->method('updateMultiple')
            ->with(
                AbstractRdsResultStorage::VARIABLES_TABLENAME,
                [
                    [
                        'conditions' => [
                            'variable_id' => 888,
                        ],
                        'updateValues' => [
                            'call_id_test' => 'callTestId',
                        ]
                    ]
                ]
            );

        $this->instance->replaceTestVariables(
            'deliveryExecutionId',
            'testUri',
            'callTestId',
            [
                888 => $variable
            ]
        );
    }
}

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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT); *
 *
 *
 */

namespace oat\taoOutcomeRds\test\unit\model;

use common_persistence_Manager;
use oat\generis\test\TestCase;
use oat\taoOutcomeRds\model\RdsResultStorage;
use oat\taoOutcomeRds\scripts\install\createTables;
use Prophecy\Argument;

/**
 * Test Rds result storage
 *
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 * @package taoOutcomeRds
 *
 */
class RdsResultStorageTest extends TestCase
{
    /**
     * @var RdsResultStorage
     */
    protected $instance;

    public function setUp()
    {
        $databaseMock = $this->getSqlMock('rds_result_storage_test');
        $persistance = $databaseMock->getPersistenceById('rds_result_storage_test');

        (new createTables())->generateTables($persistance);
        $persistanceManagerProphecy = $this->prophesize(common_persistence_Manager::class);
        $persistanceManagerProphecy->getPersistenceById(Argument::any())->willReturn($persistance);
        $serviceManagerMock = $this->getServiceLocatorMock([
            common_persistence_Manager::SERVICE_ID => $persistanceManagerProphecy,
        ]);

        $this->instance = new RdsResultStorage();
        $this->instance->setOption(RdsResultStorage::OPTION_PERSISTENCE, $persistance);
        $this->instance->setServiceLocator($serviceManagerMock);
    }

    public function tearDown()
    {
        $this->instance = null;
    }

    public function testStoreRelatedTestTaker()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $testTakerIdentifier = "mytestTaker#1";
        $this->instance->storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier);

        $this->assertSame($testTakerIdentifier, $this->instance->getTestTaker($deliveryResultIdentifier));
    }

    public function testStoreRelatedDelivery()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $deliveryIdentifier = "myDelivery#1";
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier);

        $this->assertSame($deliveryIdentifier, $this->instance->getDelivery($deliveryResultIdentifier));
    }

    public function testGetAllTestTakerIds()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $testTakerIdentifier = "mytestTaker#1";
        $this->instance->storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier);

        $expected = [
            [
                'deliveryResultIdentifier' => $deliveryResultIdentifier,
                'testTakerIdentifier' => $testTakerIdentifier,
            ],
        ];

        $this->assertSame($expected, $this->instance->getAllTestTakerIds());
    }

    public function testGetAllDeliveryIds()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $deliveryIdentifier = "mytestTaker#1";
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier);

        $expected = [
            [
                'deliveryResultIdentifier' => $deliveryResultIdentifier,
                'deliveryIdentifier' => $deliveryIdentifier,
            ],
        ];

        $this->assertSame($expected, $this->instance->getAllDeliveryIds());
    }

    public function testCountResultByDelivery()
    {
        $deliveryResultIdentifier1 = "MyDeliveryResultIdentifier#1";
        $deliveryResultIdentifier2 = "MyDeliveryResultIdentifier#2";
        $testTakerIdentifier1 = "mytestTaker#1";
        $testTakerIdentifier2 = "mytestTaker#2";
        $deliveryIdentifier1 = "myDelivery#1";
        $deliveryIdentifier2 = "myDelivery#2";

        $this->instance->storeRelatedTestTaker($deliveryResultIdentifier1, $testTakerIdentifier1);
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier1, $deliveryIdentifier1);
        $this->instance->storeRelatedTestTaker($deliveryResultIdentifier2, $testTakerIdentifier2);
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier2, $deliveryIdentifier2);

        $this->assertEquals(2, $this->instance->countResultByDelivery([]));
        $this->assertEquals(1, $this->instance->countResultByDelivery([$deliveryIdentifier1]));
    }

    /**
     * @dataProvider resultByDeliveryToTest
     *
     * @param $ids
     * @param $selected
     * @param $options
     * @param $expected
     */
    public function testGetResultByDelivery($ids, $selected, $options, $expected)
    {
        $this->instance->storeRelatedTestTaker($ids['dr11'], $ids['tt1']);
        $this->instance->storeRelatedDelivery($ids['dr11'], $ids['d1']);
        $this->instance->storeRelatedTestTaker($ids['dr12'], $ids['tt2']);
        $this->instance->storeRelatedDelivery($ids['dr12'], $ids['d1']);
        $this->instance->storeRelatedTestTaker($ids['dr21'], $ids['tt1']);
        $this->instance->storeRelatedDelivery($ids['dr21'], $ids['d2']);
        $this->instance->storeRelatedTestTaker($ids['dr22'], $ids['tt2']);
        $this->instance->storeRelatedDelivery($ids['dr22'], $ids['d2']);

        foreach ($expected as &$fields) {
            $fields = [
                'deliveryResultIdentifier' => $ids[$fields[0]],
                'testTakerIdentifier' => $ids[$fields[1]],
                'deliveryIdentifier' => $ids[$fields[2]],
            ];
        }

        $this->assertEquals($expected, $this->instance->getResultByDelivery($selected, $options));
    }

    public function resultByDeliveryToTest()
    {
        $ids = [
            'dr11' => 'MyDeliveryResultIdentifier#11',
            'dr12' => 'MyDeliveryResultIdentifier#12',
            'dr21' => 'MyDeliveryResultIdentifier#21',
            'dr22' => 'MyDeliveryResultIdentifier#22',
            'tt1' => 'mytestTaker#1',
            'tt2' => 'mytestTaker#2',
            'd1' => 'myDelivery#1',
            'd2' => 'myDelivery#2',
        ];

        return [
            'all deliveries' => [
                $ids,
                [],
                [],
                [
                    ['dr11', 'tt1', 'd1'],
                    ['dr12', 'tt2', 'd1'],
                    ['dr21', 'tt1', 'd2'],
                    ['dr22', 'tt2', 'd2'],
                ],
            ],
            'delivery1' => [
                $ids,
                [$ids['d1']],
                ['order' => RdsResultStorage::DELIVERY_COLUMN],
                [
                    ['dr11', 'tt1', 'd1'],
                    ['dr12', 'tt2', 'd1'],
                ],
            ],
            'delivery1+2 by testtaker desc' => [
                $ids,
                [$ids['d1'], $ids['d2']],
                ['order' => RdsResultStorage::TEST_TAKER_COLUMN, 'orderdir' => 'desc'],
                [
                    ['dr12', 'tt2', 'd1'],
                    ['dr22', 'tt2', 'd2'],
                    ['dr11', 'tt1', 'd1'],
                    ['dr21', 'tt1', 'd2'],
                ],
            ],
            'limit + offset' => [
                $ids,
                [],
                ['order' => RdsResultStorage::RESULTS_TABLE_ID, 'limit' => 2, 'offset' => 1],
                [
                    ['dr12', 'tt2', 'd1'],
                    ['dr21', 'tt1', 'd2'],
                ],
            ],
            'not existing delivery' => [
                $ids,
                ['not existing delivery'],
                [],
                [],
            ],
        ];
    }

    public function testDeleteResult()
    {
        $deliveryResultIdentifier1 = "MyDeliveryResultIdentifier#1";
        $deliveryResultIdentifier2 = "MyDeliveryResultIdentifier#2";
        $testTakerIdentifier1 = "mytestTaker#1";
        $testTakerIdentifier2 = "mytestTaker#2";
        $deliveryIdentifier1 = "myDelivery#1";
        $deliveryIdentifier2 = "myDelivery#2";

        $this->instance->storeRelatedTestTaker($deliveryResultIdentifier1, $testTakerIdentifier1);
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier1, $deliveryIdentifier1);
        $this->instance->storeRelatedTestTaker($deliveryResultIdentifier2, $testTakerIdentifier2);
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier2, $deliveryIdentifier2);

        $this->assertEquals(2, $this->instance->countResultByDelivery([]));
        $this->assertTrue($this->instance->deleteResult($deliveryResultIdentifier1));
        $this->assertEquals(1, $this->instance->countResultByDelivery([]));
    }

    ////////////////////////////////////////////////////////////////////////////
    /// Variables storing

    public function testStoreItemVariable()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#2";
        $item = "MyGreatItem#2";
        $callId = "MyCallId#2";

        $itemVariable = new \taoResultServer_models_classes_OutcomeVariable();
        $itemVariable->setBaseType('float');
        $itemVariable->setCardinality('multiple');
        $itemVariable->setIdentifier('Identifier');
        $itemVariable->setValue('MyValue');

        $this->instance->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $callId);
        $tmp = $this->instance->getVariable($callId, 'Identifier');

        $object = array_shift($tmp);
        $this->assertEquals($test, $object->test);
        $this->assertEquals($item, $object->item);
        $this->assertEquals('float', $object->variable->getBaseType());
        $this->assertEquals('multiple', $object->variable->getCardinality());
        $this->assertEquals('Identifier', $object->variable->getIdentifier());
        $this->assertEquals('MyValue', $object->variable->getValue());
        $this->assertInstanceOf('taoResultServer_models_classes_OutcomeVariable', $object->variable);

        $this->assertEquals('float', $this->instance->getVariableProperty($object->uri, 'baseType'));
        $this->assertEquals('multiple', $this->instance->getVariableProperty($object->uri, 'cardinality'));
        $this->assertEquals('Identifier', $this->instance->getVariableProperty($object->uri, 'identifier'));
        $this->assertEquals('MyValue', $this->instance->getVariableProperty($object->uri, 'value'));
        $this->assertNull($this->instance->getVariableProperty($object->uri, 'unknownProperty'));
    }

    public function testStoreTestVariable()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#3";
        $callId = "MyCallId#3";

        $testVariable = new \taoResultServer_models_classes_OutcomeVariable();
        $testVariable->setBaseType('float');
        $testVariable->setCardinality('multiple');
        $testVariable->setIdentifier('TestIdentifier');
        $testVariable->setValue('MyValue');

        $this->instance->storeTestVariable($deliveryResultIdentifier, $test, $testVariable, $callId);
        $tmp = $this->instance->getVariable($callId, 'TestIdentifier');
        $object = array_shift($tmp);
        $this->assertEquals($test, $object->test);
        $this->assertNull($object->item);
        $this->assertEquals('float', $object->variable->getBaseType());
        $this->assertEquals('multiple', $object->variable->getCardinality());
        $this->assertEquals('TestIdentifier', $object->variable->getIdentifier());
        $this->assertEquals('MyValue', $object->variable->getValue());
        $this->assertInstanceOf('taoResultServer_models_classes_OutcomeVariable', $object->variable);

        $this->assertEquals('float', $this->instance->getVariableProperty($object->uri, 'baseType'));
        $this->assertEquals('multiple', $this->instance->getVariableProperty($object->uri, 'cardinality'));
        $this->assertEquals('TestIdentifier', $this->instance->getVariableProperty($object->uri, 'identifier'));
        $this->assertEquals('MyValue', $this->instance->getVariableProperty($object->uri, 'value'));
    }
}

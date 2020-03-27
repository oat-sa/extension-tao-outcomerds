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

use oat\generis\persistence\PersistenceManager;
use oat\generis\test\TestCase;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;
use oat\taoOutcomeRds\model\RdsResultStorage;
use oat\taoOutcomeRds\scripts\install\CreateTables;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;

/**
 * Test Rds result storage
 */
class RdsResultStorageTest extends TestCase
{
    /**
     * @var RdsResultStorage
     */
    protected $instance;

    public function setUp(): void
    {
        $persistenceId = 'rds_result_storage_test';
        $persistenceManager = $this->getSqlMock($persistenceId);

        $testedClass = $this->getTestedClass();
        $this->instance = new $testedClass([$testedClass::OPTION_PERSISTENCE => $persistenceId]);

        $serviceManagerMock = $this->getServiceLocatorMock([
            PersistenceManager::SERVICE_ID => $persistenceManager,
            $testedClass::SERVICE_ID => $this->instance,
        ]);
        $this->instance->setServiceLocator($serviceManagerMock);

        $createTableAction = new CreateTables();
        $createTableAction->setServiceLocator($serviceManagerMock);
        $createTableAction->__invoke(null);
    }

    public function tearDown(): void
    {
        $this->instance = null;
    }

    protected function getTestedClass()
    {
        return RdsResultStorage::class;
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
        $this->assertEquals(1, $this->instance->countResultByDelivery($deliveryIdentifier1));
    }

    /**
     * @dataProvider resultByDeliveryToTest
     *
     * @param array $ids
     * @param array|string $selected
     * @param array $options
     * @param array $expected
     */
    public function testGetResultByDelivery(array $ids, $selected, array $options, array $expected)
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
                AbstractRdsResultStorage::FIELD_DELIVERY_RESULT => $ids[$fields[0]],
                AbstractRdsResultStorage::FIELD_TEST_TAKER => $ids[$fields[1]],
                AbstractRdsResultStorage::FIELD_DELIVERY => $ids[$fields[2]],
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
                ['order' => AbstractRdsResultStorage::DELIVERY_COLUMN],
                [
                    ['dr11', 'tt1', 'd1'],
                    ['dr12', 'tt2', 'd1'],
                ],
            ],
            'delivery1+2 by testtaker desc' => [
                $ids,
                [$ids['d1'], $ids['d2']],
                ['order' => AbstractRdsResultStorage::TEST_TAKER_COLUMN, 'orderdir' => 'desc'],
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
                ['order' => AbstractRdsResultStorage::RESULTS_TABLE_ID, 'limit' => 2, 'offset' => 1],
                [
                    ['dr12', 'tt2', 'd1'],
                    ['dr21', 'tt1', 'd2'],
                ],
            ],
            'not existing delivery' => [
                $ids,
                'not existing delivery',
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

    public function testStoreItemVariable()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#1";
        $item = "MyGreatItem#1";
        $callId = "MyCallId#1";
        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'ItemIdentifier';
        $value = 'MyValue';

        $itemVariable = new OutcomeVariable();
        $itemVariable->setBaseType($baseType);
        $itemVariable->setCardinality($cardinality);
        $itemVariable->setIdentifier($identifier);
        $itemVariable->setValue($value);
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $this->instance->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $callId);
        $variables = $this->instance->getVariable($callId, $identifier);

        $object = array_shift($variables);
        $this->assertItemVariableBasics($test, $object, $item);

        $this->assertVariable($baseType, $object, $cardinality, $identifier, $value);
    }

    public function testStoreItemVariableException()
    {
        $this->expectException(DuplicateVariableException::class);
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#3";
        $test = "MyGreatTest#1";
        $item = "MyGreatItem#1";
        $callId = "MyCallId#3";
        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'ItemIdentifier';
        $value = 'MyValue';

        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $itemVariable = new OutcomeVariable();
        $itemVariable->setBaseType($baseType);
        $itemVariable->setCardinality($cardinality);
        $itemVariable->setIdentifier($identifier);
        $itemVariable->setValue($value);

        $this->instance->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $callId);
        $this->instance->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $callId);
    }

    public function testStoreItemVariablesException()
    {
        $this->expectException(DuplicateVariableException::class);
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#4";
        $test = "MyGreatTest#1";
        $item = "MyGreatItem#1";
        $callId = "MyCallId#3";
        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'ItemIdentifier';
        $value = 'MyValue';

        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $itemVariable = new OutcomeVariable();
        $itemVariable->setBaseType($baseType);
        $itemVariable->setCardinality($cardinality);
        $itemVariable->setIdentifier($identifier);
        $itemVariable->setValue($value);

        $this->instance->storeItemVariables($deliveryResultIdentifier, $test, $item, [$itemVariable, $itemVariable], $callId);
    }

    public function testStoreItemVariables()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#1";
        $item = "MyGreatItem#1";
        $callId = "MyCallId#1";
        $baseType1 = 'float';
        $cardinality1 = 'multiple';
        $identifier1 = 'ItemIdentifier1';
        $value1 = 'MyValue1';
        $baseType2 = 'string';
        $cardinality2 = 'ordered';
        $identifier2 = 'ItemIdentifier2';
        $value2 = 'MyValue2';

        $itemVariable1 = new OutcomeVariable();
        $itemVariable1->setBaseType($baseType1);
        $itemVariable1->setCardinality($cardinality1);
        $itemVariable1->setIdentifier($identifier1);
        $itemVariable1->setValue($value1);

        $itemVariable2 = new OutcomeVariable();
        $itemVariable2->setBaseType($baseType2);
        $itemVariable2->setCardinality($cardinality2);
        $itemVariable2->setIdentifier($identifier2);
        $itemVariable2->setValue($value2);

        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $duplicateExceptionThrown = false;
        try {
            $this->instance->storeItemVariables($deliveryResultIdentifier, $test, $item, [$itemVariable1, $itemVariable1, $itemVariable2], $callId);
        } catch (DuplicateVariableException $e) {
            $duplicateExceptionThrown = true;
        }

        $this->assertTrue($duplicateExceptionThrown, 'DuplicateVariableException has been thrown');
        $variables = $this->instance->getVariables($callId);

        $object = array_shift($variables)[0];
        $this->assertItemVariableBasics($test, $object, $item);
        $this->assertEquals($baseType1, $object->variable->getBaseType());
        $this->assertEquals($cardinality1, $object->variable->getCardinality());
        $this->assertEquals($identifier1, $object->variable->getIdentifier());
        $this->assertEquals($value1, $object->variable->getValue());

        $object = array_shift($variables)[0];
        $this->assertItemVariableBasics($test, $object, $item);
        $this->assertEquals($baseType2, $object->variable->getBaseType());
        $this->assertEquals($cardinality2, $object->variable->getCardinality());
        $this->assertEquals($identifier2, $object->variable->getIdentifier());
        $this->assertEquals($value2, $object->variable->getValue());
    }

    public function testStoreTestVariable()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#1";
        $callId = "MyCallId#1";
        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'TestIdentifier';
        $value = 'MyValue';

        $testVariable = new OutcomeVariable();
        $testVariable->setBaseType($baseType);
        $testVariable->setCardinality($cardinality);
        $testVariable->setIdentifier($identifier);
        $testVariable->setValue($value);
        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');
        $this->instance->storeTestVariable($deliveryResultIdentifier, $test, $testVariable, $callId);
        $variables = $this->instance->getVariable($callId, $identifier);

        $object = array_shift($variables);
        $this->assertTestVariableBasics($test, $object);
        $this->assertVariable($baseType, $object, $cardinality, $identifier, $value);
    }

    public function testStoreTestVariables()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#1";
        $callId = "MyCallId#1";
        $baseType1 = 'float';
        $cardinality1 = 'multiple';
        $identifier1 = 'ItemIdentifier1';
        $value1 = 'MyValue1';
        $baseType2 = 'string';
        $cardinality2 = 'ordered';
        $identifier2 = 'ItemIdentifier2';
        $value2 = 'MyValue2';

        $itemVariable1 = new OutcomeVariable();
        $itemVariable1->setBaseType($baseType1);
        $itemVariable1->setCardinality($cardinality1);
        $itemVariable1->setIdentifier($identifier1);
        $itemVariable1->setValue($value1);

        $itemVariable2 = new OutcomeVariable();
        $itemVariable2->setBaseType($baseType2);
        $itemVariable2->setCardinality($cardinality2);
        $itemVariable2->setIdentifier($identifier2);
        $itemVariable2->setValue($value2);

        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $this->instance->storeTestVariables($deliveryResultIdentifier, $test, [$itemVariable1, $itemVariable2], $callId);
        $variables = $this->instance->getDeliveryVariables($deliveryResultIdentifier);

        $object = array_shift($variables)[0];
        $this->assertTestVariableBasics($test, $object);
        $this->assertVariable($baseType1, $object, $cardinality1, $identifier1, $value1);

        $object = array_shift($variables)[0];
        $this->assertTestVariableBasics($test, $object);
        $this->assertVariable($baseType2, $object, $cardinality2, $identifier2, $value2);
    }

    public function testGetAllCallIds()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#1";
        $item = "MyGreatItem#1";
        $testCallId = "testCallId#1";
        $itemCallId = "itemCallId#1";

        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'ItemIdentifier1';
        $value = 'MyValue1';

        $itemVariable = new OutcomeVariable();
        $itemVariable->setBaseType($baseType);
        $itemVariable->setCardinality($cardinality);
        $itemVariable->setIdentifier($identifier);
        $itemVariable->setValue($value);

        $testVariable = new OutcomeVariable();
        $testVariable->setBaseType($baseType);
        $testVariable->setCardinality($cardinality);
        $testVariable->setIdentifier($identifier);
        $testVariable->setValue($value);

        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $this->instance->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $itemCallId);
        $this->instance->storeTestVariable($deliveryResultIdentifier, $test, $testVariable, $testCallId);

        $this->assertSame([$testCallId, $itemCallId], $this->instance->getAllCallIds());
    }

    public function testGetRelatedItemCallIds()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#1";
        $item = "MyGreatItem#1";
        $testCallId = "testCallId#1";
        $itemCallId = "itemCallId#1";

        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'ItemIdentifier1';
        $value = 'MyValue1';

        $itemVariable = new OutcomeVariable();
        $itemVariable->setBaseType($baseType);
        $itemVariable->setCardinality($cardinality);
        $itemVariable->setIdentifier($identifier);
        $itemVariable->setValue($value);

        $testVariable = new OutcomeVariable();
        $testVariable->setBaseType($baseType);
        $testVariable->setCardinality($cardinality);
        $testVariable->setIdentifier($identifier);
        $testVariable->setValue($value);

        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $this->instance->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $itemCallId);
        $this->instance->storeTestVariable($deliveryResultIdentifier, $test, $testVariable, $testCallId);

        $this->assertSame([$itemCallId], $this->instance->getRelatedItemCallIds($deliveryResultIdentifier));
    }

    public function testGetRelatedTestCallIds()
    {
        $deliveryResultIdentifier = "MyDeliveryResultIdentifier#1";
        $test = "MyGreatTest#1";
        $item = "MyGreatItem#1";
        $testCallId = "testCallId#1";
        $itemCallId = "itemCallId#1";

        $baseType = 'float';
        $cardinality = 'multiple';
        $identifier = 'ItemIdentifier1';
        $value = 'MyValue1';

        $itemVariable = new OutcomeVariable();
        $itemVariable->setBaseType($baseType);
        $itemVariable->setCardinality($cardinality);
        $itemVariable->setIdentifier($identifier);
        $itemVariable->setValue($value);

        $testVariable = new OutcomeVariable();
        $testVariable->setBaseType($baseType);
        $testVariable->setCardinality($cardinality);
        $testVariable->setIdentifier($identifier);
        $testVariable->setValue($value);

        $this->instance->storeRelatedDelivery($deliveryResultIdentifier, 'delivery');

        $this->instance->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $itemCallId);
        $this->instance->storeTestVariable($deliveryResultIdentifier, $test, $testVariable, $testCallId);

        $this->assertSame([$testCallId], $this->instance->getRelatedTestCallIds($deliveryResultIdentifier));
    }

    /**
     * @param string $baseType
     * @param $object
     * @param string $cardinality
     * @param string $identifier
     * @param string $value
     */
    protected function assertVariable( string $baseType, $object, string $cardinality, string $identifier, string $value ): void {
        $this->assertEquals($baseType, $object->variable->getBaseType());
        $this->assertEquals($cardinality, $object->variable->getCardinality());
        $this->assertEquals($identifier, $object->variable->getIdentifier());
        $this->assertEquals($value, $object->variable->getValue());

        $this->assertEquals($baseType, $this->instance->getVariableProperty($object->uri, 'baseType'));
        $this->assertEquals($cardinality, $this->instance->getVariableProperty($object->uri, 'cardinality'));
        $this->assertEquals($identifier, $this->instance->getVariableProperty($object->uri, 'identifier'));
        $this->assertEquals($value, $this->instance->getVariableProperty($object->uri, 'value'));
        $this->assertNull($this->instance->getVariableProperty($object->uri, 'unknownProperty'));
    }

    /**
     * @param string $test
     * @param $object
     * @param string $item
     */
    protected function assertItemVariableBasics(string $test, $object, string $item): void
    {
        $this->assertEquals($test, $object->test);
        $this->assertEquals($item, $object->item);
        $this->assertInstanceOf(OutcomeVariable::class, $object->variable);
    }

    /**
     * @param string $test
     * @param $object
     */
    protected function assertTestVariableBasics(string $test, $object): void
    {
        $this->assertEquals($test, $object->test);
        $this->assertNull($object->item);
        $this->assertInstanceOf(OutcomeVariable::class, $object->variable);
    }
}

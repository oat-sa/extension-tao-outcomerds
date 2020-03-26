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

use oat\taoOutcomeRds\model\NewSqlResultStorage;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;

/**
 * Test NewSql result storage
 */
class NewSqlResultStorageTest extends RdsResultStorageTest
{
    protected function getTestedClass()
    {
        return NewSqlResultStorage::class;
    }

    /**
     * @dataProvider microTimeToTest
     * @param $microTime
     * @param $expected
     */
    public function testMicroTimeToMicroSeconds($microTime, $expected)
    {
        $this->assertEquals($expected, $this->instance->getDateFromMicroTime($microTime, 'Y-m-d\TH:i:s.u\Z'));
    }

    public function microTimeToTest()
    {
        return [
            [0.0, new \DateTime('1970-01-01T00:00:00.000000Z')],
            [1234567890.12345600, new \DateTime('2009-02-13T23:31:30.123456Z')],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function assertItemVariableBasics(string $test, $object, string $item): void
    {
        $this->assertEquals('deprecated', $object->test);
        $this->assertEquals($item, $object->item);
        $this->assertInstanceOf(OutcomeVariable::class, $object->variable);
    }

    /**
     * @inheritDoc
     */
    protected function assertTestVariableBasics(string $test, $object): void
    {
        $this->assertEquals('deprecated', $object->test);
        $this->assertNull($object->item);
        $this->assertInstanceOf(OutcomeVariable::class, $object->variable);
    }
}

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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\taoOutcomeRds\model;

use Doctrine\DBAL\Schema\Schema;
use taoResultServer_models_classes_Variable as Variable;

/**
 * Implements tao results storage for NewSql
 */
class NewSqlResultStorage extends AbstractRdsResultStorage
{
    const CREATED_AT = 'created_at';

    /**
     * @inheritDoc
     */
    protected function getVariablesSortingField()
    {
        return self::CREATED_AT;
    }

    /**
     * @inheritdoc
     */
    protected function prepareVariableDataForSchema($deliveryResultIdentifier, $test, Variable $variable, $callId)
    {
        $serializedVariable = $this->serializeVariableValue($variable);

        $persistence = $this->getPersistence();

        $createdAt = $this->getDateFromMicroTime($variable->getCreationTime());

        return [
            self::VARIABLES_TABLE_ID => $persistence->getUniquePrimaryKey(),
            self::VARIABLES_FK_COLUMN => $deliveryResultIdentifier,
            self::TEST_COLUMN => 'deprecated',
            self::VARIABLE_IDENTIFIER => $variable->getIdentifier(),
            self::VARIABLE_VALUE => $serializedVariable,
            self::VARIABLE_HASH => $deliveryResultIdentifier . md5($deliveryResultIdentifier . $serializedVariable . $callId),
            self::CREATED_AT => $createdAt->format($persistence->getPlatform()->getDateTimeFormatString()),
        ];
    }

    /**
     * Converts float microtime to a DateTime.
     * @param string $microTime
     *
     * @return \DateTime
     */
    public function getDateFromMicroTime($microTime)
    {
        return date_create_from_format('U.u', number_format($microTime, 6, '.', ''));
    }

    /**
     * @inheritDoc
     */
    public function createVariablesTable(Schema $schema)
    {
        $table = $schema->createtable(self::VARIABLES_TABLENAME);
        $table->addOption('engine', 'MyISAM');

        $table->addColumn(self::VARIABLES_TABLE_ID, 'string', ['length' => 36]);
        $table->addColumn(self::CALL_ID_TEST_COLUMN, 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(self::CALL_ID_ITEM_COLUMN, 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(self::TEST_COLUMN, 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(self::ITEM_COLUMN, 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(self::VARIABLE_VALUE, 'text', ['notnull' => false]);
        $table->addColumn(self::VARIABLE_IDENTIFIER, 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(self::VARIABLES_FK_COLUMN, 'string', ['length' => 255]);
        $table->addColumn(self::VARIABLE_HASH, 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn(self::CREATED_AT, 'datetime', []);

        $table->setPrimaryKey([self::VARIABLES_TABLE_ID]);
        $table->addUniqueIndex([self::VARIABLE_HASH], self::UNIQUE_VARIABLE_INDEX);
        $table->addIndex([self::CALL_ID_ITEM_COLUMN], self::CALL_ID_ITEM_INDEX);
        $table->addIndex([self::CALL_ID_TEST_COLUMN], self::CALL_ID_TEST_INDEX);

        return $table;
    }
}

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
 *
 */

namespace oat\taoOutcomeRds\model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoResultServer\models\classes\ResultDeliveryExecutionDelete;
use oat\taoResultServer\models\classes\ResultManagement;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use Psr\Log\LoggerAwareInterface;
use taoResultServer_models_classes_ReadableResultStorage as ReadableResultStorage;
use taoResultServer_models_classes_Variable as Variable;
use taoResultServer_models_classes_WritableResultStorage as WritableResultStorage;

/**
 * Implements tao results storage using the configured persistence "taoOutcomeRds"
 */
abstract class AbstractRdsResultStorage extends ConfigurableService implements WritableResultStorage, ReadableResultStorage, ResultManagement, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ResultDeliveryExecutionDelete;

    const SERVICE_ID = 'taoOutcomeRds/RdsResultStorage';
    /**
     * Constants for the database creation and data access
     */
    const RESULTS_TABLENAME = 'results_storage';
    const RESULTS_TABLE_ID = 'result_id';
    const TEST_TAKER_COLUMN = 'test_taker';
    const DELIVERY_COLUMN = 'delivery';
    const VARIABLES_TABLENAME = 'variables_storage';
    const VARIABLES_TABLE_ID = 'variable_id';
    const CALL_ID_ITEM_COLUMN = 'call_id_item';
    const CALL_ID_TEST_COLUMN = 'call_id_test';
    const TEST_COLUMN = 'test';
    const ITEM_COLUMN = 'item';
    const VARIABLE_VALUE = 'value';
    const VARIABLE_IDENTIFIER = 'identifier';
    const VARIABLE_HASH = 'variable_hash';
    const CALL_ID_ITEM_INDEX = 'idx_variables_storage_call_id_item';
    const CALL_ID_TEST_INDEX = 'idx_variables_storage_call_id_test';
    const UNIQUE_VARIABLE_INDEX = 'idx_unique_variables_storage';
    /** @deprecated */
    const VARIABLE_CLASS = 'class';
    const VARIABLES_FK_COLUMN = 'results_result_id';
    const VARIABLES_FK_NAME = 'fk_variables_results';
    /** @deprecated */
    const RESULT_KEY_VALUE_TABLE_NAME = 'results_kv_storage';
    /** @deprecated */
    const KEY_COLUMN = 'result_key';
    /** @deprecated */
    const VALUE_COLUMN = 'result_value';
    /** @deprecated */
    const RESULTSKV_FK_COLUMN = 'variables_variable_id';
    /** @deprecated */
    const RESULTSKV_FK_NAME = 'fk_resultsKv_variables';
    /** result storage persistence identifier */
    const OPTION_PERSISTENCE = 'persistence';
    // Fields for results retrieval.
    const FIELD_DELIVERY_RESULT = 'deliveryResultIdentifier';
    const FIELD_TEST_TAKER = 'testTakerIdentifier';
    const FIELD_DELIVERY = 'deliveryIdentifier';

    /** @var */
    protected $persistence;

    public function storeTestVariable($deliveryResultIdentifier, $test, Variable $testVariable, $callIdTest)
    {
        $this->storeTestVariables($deliveryResultIdentifier, $test, [$testVariable], $callIdTest);
    }

    /**
     * @inheritdoc
     * Stores the test variables in table and their values in key/value storage.
     */
    public function storeTestVariables($deliveryResultIdentifier, $test, array $testVariables, $callIdTest)
    {
        $dataToInsert = [];

        foreach ($testVariables as $testVariable) {
            $dataToInsert[] = $this->prepareTestVariableData(
                $deliveryResultIdentifier,
                $test,
                $testVariable,
                $callIdTest
            );
        }

        $this->insertMultiple($dataToInsert);
    }

    /**
     * @inheritdoc
     * Stores the item in table and its value in key/value storage.
     */
    public function storeItemVariable($deliveryResultIdentifier, $test, $item, Variable $itemVariable, $callIdItem)
    {
        $this->storeItemVariables($deliveryResultIdentifier, $test, $item, [$itemVariable], $callIdItem);
    }

    /**
     * @inheritdoc
     * Stores the item variables in table and their values in key/value storage.
     */
    public function storeItemVariables($deliveryResultIdentifier, $test, $item, array $itemVariables, $callIdItem)
    {
        $dataToInsert = [];

        foreach ($itemVariables as $itemVariable) {
            $dataToInsert[] = $this->prepareItemVariableData(
                $deliveryResultIdentifier,
                $test,
                $item,
                $itemVariable,
                $callIdItem
            );
        }

        $this->insertMultiple($dataToInsert);
    }

    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier)
    {
        $this->storeRelatedData($deliveryResultIdentifier, self::TEST_TAKER_COLUMN, $testTakerIdentifier);
    }

    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier)
    {
        $this->storeRelatedData($deliveryResultIdentifier, self::DELIVERY_COLUMN, $deliveryIdentifier);
    }

    /**
     * Store Delivery corresponding to the current test
     *
     * @param string $deliveryResultIdentifier
     * @param string $relatedField
     * @param string $relatedIdentifier
     */
    public function storeRelatedData($deliveryResultIdentifier, $relatedField, $relatedIdentifier)
    {
        $qb = $this->getQueryBuilder()
            ->select('COUNT(*)')
            ->from(self::RESULTS_TABLENAME)
            ->andWhere(self::RESULTS_TABLE_ID . ' = :id')
            ->setParameter('id', $deliveryResultIdentifier);
        if ((int)$qb->execute()->fetchColumn() === 0) {
            $this->getPersistence()->insert(
                self::RESULTS_TABLENAME,
                [
                    self::RESULTS_TABLE_ID => $deliveryResultIdentifier,
                    $relatedField => $relatedIdentifier,
                ]
            );
        } else {
            $sqlUpdate = 'UPDATE ' . self::RESULTS_TABLENAME . ' SET ' . $relatedField . ' = ? WHERE ' . self::RESULTS_TABLE_ID . ' = ?';
            $paramsUpdate = [$relatedIdentifier, $deliveryResultIdentifier];
            $this->getPersistence()->exec($sqlUpdate, $paramsUpdate);
        }
    }

    public function getVariables($callId)
    {
        if (!is_array($callId)) {
            $callId = [$callId];
        }

        $qb = $this->getQueryBuilder()
            ->select('*')
            ->from(self::VARIABLES_TABLENAME)
            ->andWhere(self::CALL_ID_ITEM_COLUMN . ' IN (:ids) OR ' . self::CALL_ID_TEST_COLUMN . ' IN (:ids)')
            ->orderBy($this->getVariablesSortingField())
            ->setParameter('ids', $callId, Connection::PARAM_STR_ARRAY);

        $returnValue = [];
        foreach ($qb->execute()->fetchAll() as $variable) {
            $returnValue[$variable[self::VARIABLES_TABLE_ID]][] = $this->getResultRow($variable);
        }

        return $returnValue;
    }

    public function getDeliveryVariables($deliveryResultIdentifier)
    {
        if (!is_array($deliveryResultIdentifier)) {
            $deliveryResultIdentifier = [$deliveryResultIdentifier];
        }

        $qb = $this->getQueryBuilder()
            ->select('*')
            ->from(self::VARIABLES_TABLENAME)
            ->andWhere(self::VARIABLES_FK_COLUMN . ' IN (:ids)')
            ->orderBy($this->getVariablesSortingField())
            ->setParameter('ids', $deliveryResultIdentifier, Connection::PARAM_STR_ARRAY);

        $returnValue = [];
        foreach ($qb->execute()->fetchAll() as $variable) {
            $returnValue[$variable[self::VARIABLES_TABLE_ID]][] = $this->getResultRow($variable);
        }

        return $returnValue;
    }

    public function getVariable($callId, $variableIdentifier)
    {
        $qb = $this->getQueryBuilder()
            ->select('*')
            ->from(self::VARIABLES_TABLENAME)
            ->andWhere(self::CALL_ID_ITEM_COLUMN . ' = :callId OR ' . self::CALL_ID_TEST_COLUMN . ' = :callId')
            ->andWhere(self::VARIABLE_IDENTIFIER . ' = :variableId')
            ->setParameter('callId', $callId)
            ->setParameter('variableId', $variableIdentifier);

        $returnValue = [];
        foreach ($qb->execute()->fetchAll() as $variable) {
            $returnValue[$variable[self::VARIABLES_TABLE_ID]] = $this->getResultRow($variable);
        }

        return $returnValue;
    }

    public function getVariableProperty($variableId, $property)
    {
        $qb = $this->getQueryBuilder()
            ->select(self::VARIABLE_VALUE)
            ->from(self::VARIABLES_TABLENAME)
            ->andWhere(self::VARIABLES_TABLE_ID . ' = :variableId')
            ->setParameter('variableId', $variableId);

        $variableValue = $qb->execute()->fetchColumn();
        $variableValue = $this->unserializeVariableValue($variableValue);
        $getter = 'get' . ucfirst($property);
        if (is_callable([$variableValue, $getter])) {
            return $variableValue->$getter();
        }

        return null;
    }

    /**
     * Returns the field to sort item and test variables.
     *
     * @return string
     */
    abstract protected function getVariablesSortingField();

    public function getTestTaker($deliveryResultIdentifier)
    {
        return $this->getRelatedData($deliveryResultIdentifier, self::TEST_TAKER_COLUMN);
    }

    public function getDelivery($deliveryResultIdentifier)
    {
        return $this->getRelatedData($deliveryResultIdentifier, self::DELIVERY_COLUMN);
    }

    /**
     * Retrieves data related to a result.
     *
     * @param string $deliveryResultIdentifier
     * @param string $field
     *
     * @return mixed
     */
    public function getRelatedData($deliveryResultIdentifier, $field)
    {
        $qb = $this->getQueryBuilder()
            ->select($field)
            ->from(self::RESULTS_TABLENAME)
            ->andWhere(self::RESULTS_TABLE_ID . ' = :id')
            ->setParameter('id', $deliveryResultIdentifier);

        return $qb->execute()->fetchColumn();
    }

    /**
     * @inheritdoc
     * o(n) do not use real time (postprocessing)
     */
    public function getAllCallIds()
    {
        $qb = $this->getQueryBuilder()
            ->select('DISTINCT(' . self::CALL_ID_ITEM_COLUMN . '), ' . self::CALL_ID_TEST_COLUMN . ', ' . self::VARIABLES_FK_COLUMN)
            ->from(self::VARIABLES_TABLENAME);

        $returnValue = [];
        foreach ($qb->execute()->fetchAll() as $value) {
            $returnValue[] = ($value[self::CALL_ID_ITEM_COLUMN] != '')
                ? $value[self::CALL_ID_ITEM_COLUMN]
                : $value[self::CALL_ID_TEST_COLUMN];
        }

        return $returnValue;
    }

    public function getRelatedItemCallIds($deliveryResultIdentifier)
    {
        return $this->getRelatedCallIds($deliveryResultIdentifier, self::CALL_ID_ITEM_COLUMN);
    }

    public function getRelatedTestCallIds($deliveryResultIdentifier)
    {
        return $this->getRelatedCallIds($deliveryResultIdentifier, self::CALL_ID_TEST_COLUMN);
    }

    public function getRelatedCallIds($deliveryResultIdentifier, $field)
    {
        $qb = $this->getQueryBuilder()
            ->select('DISTINCT(' . $field . ')')
            ->from(self::VARIABLES_TABLENAME)
            ->andWhere(self::VARIABLES_FK_COLUMN . ' = :id AND ' . $field . ' <> :field')
            ->setParameter('id', $deliveryResultIdentifier)
            ->setParameter('field', '');

        $returnValue = [];
        foreach ($qb->execute()->fetchAll() as $value) {
            if (isset($value[$field])) {
                $returnValue[] = $value[$field];
            }
        }

        return $returnValue;
    }

    public function getAllTestTakerIds()
    {
        return $this->getAllIds(self::FIELD_TEST_TAKER, self::TEST_TAKER_COLUMN);
    }

    public function getAllDeliveryIds()
    {
        return $this->getAllIds(self::FIELD_DELIVERY, self::DELIVERY_COLUMN);
    }

    public function getAllIds($fieldName, $field)
    {
        $qb = $this->getQueryBuilder()
            ->select(self::RESULTS_TABLE_ID . ', ' . $field)
            ->from(self::RESULTS_TABLENAME);

        $returnValue = [];
        foreach ($qb->execute()->fetchAll() as $value) {
            $returnValue[] = [
                self::FIELD_DELIVERY_RESULT => $value[self::RESULTS_TABLE_ID],
                $fieldName => $value[$field],
            ];
        }

        return $returnValue;
    }

    public function getResultByDelivery($delivery, $options = [])
    {
        if (!is_array($delivery)) {
            $delivery = [$delivery];
        }
        $qb = $this->getQueryBuilder()
            ->select('*')
            ->from(self::RESULTS_TABLENAME)
            ->orderBy($this->getOrderField($options), $this->getOrderDirection($options));

        if (isset($options['offset'])) {
            $qb->setFirstResult($options['offset']);
        }
        if (isset($options['limit'])) {
            $qb->setMaxResults($options['limit']);
        }

        if (count($delivery) > 0) {
            $qb
                ->andWhere(self::DELIVERY_COLUMN . ' IN (:delivery)')
                ->setParameter(':delivery', $delivery, Connection::PARAM_STR_ARRAY);
        }

        $returnValue = [];
        foreach ($qb->execute()->fetchAll() as $value) {
            $returnValue[] = [
                self::FIELD_DELIVERY_RESULT => $value[self::RESULTS_TABLE_ID],
                self::FIELD_TEST_TAKER => $value[self::TEST_TAKER_COLUMN],
                self::FIELD_DELIVERY => $value[self::DELIVERY_COLUMN],
            ];
        }

        return $returnValue;
    }

    /**
     * Generates and sanitize ORDER BY field.
     *
     * @param array $options
     *
     * @return string
     */
    protected function getOrderField(array $options)
    {
        $allowedOrderFields = [self::DELIVERY_COLUMN, self::TEST_TAKER_COLUMN, self::RESULTS_TABLE_ID];

        if (isset($options['order']) && in_array($options['order'], $allowedOrderFields)) {
            return $options['order'];
        }

        return self::RESULTS_TABLE_ID;
    }

    /**
     * Generates and sanitize ORDER BY direction.
     *
     * @param array $options
     *
     * @return string
     */
    protected function getOrderDirection(array $options)
    {
        $allowedOrderDirections = ['ASC', 'DESC'];

        if (isset($options['orderdir']) && in_array(strtoupper($options['orderdir']), $allowedOrderDirections)) {
            return $options['orderdir'];
        }

        return 'ASC';
    }

    public function countResultByDelivery($delivery)
    {
        if (!is_array($delivery)) {
            $delivery = [$delivery];
        }
        $qb = $this->getQueryBuilder()
            ->select('COUNT(*)')
            ->from(self::RESULTS_TABLENAME);

        if (count($delivery) > 0) {
            $qb
                ->andWhere(self::DELIVERY_COLUMN . ' IN (:delivery)')
                ->setParameter('delivery', $delivery, Connection::PARAM_STR_ARRAY);
        }

        return $qb->execute()->fetchColumn();
    }

    public function deleteResult($deliveryResultIdentifier)
    {
        // remove variables
        $sql = 'DELETE FROM ' . self::VARIABLES_TABLENAME . '
            WHERE ' . self::VARIABLES_FK_COLUMN . ' = ?';

        if ($this->getPersistence()->exec($sql, [$deliveryResultIdentifier]) === false) {
            return false;
        }

        // remove results
        $sql = 'DELETE FROM ' . self::RESULTS_TABLENAME . '
            WHERE ' . self::RESULTS_TABLE_ID . ' = ?';

        if ($this->getPersistence()->exec($sql, [$deliveryResultIdentifier]) === false) {
            return false;
        }

        return true;
    }

    /**
     * Prepares data to be inserted in database.
     *
     * @param string $deliveryResultIdentifier
     * @param string $test
     * @param string $item
     * @param Variable $variable
     * @param string $callId
     *
     * @return array
     */
    protected function prepareItemVariableData($deliveryResultIdentifier, $test, $item, Variable $variable, $callId)
    {
        $variableData = $this->prepareVariableData($deliveryResultIdentifier, $test, $variable, $callId);
        $variableData[self::ITEM_COLUMN] = $item;
        $variableData[self::CALL_ID_ITEM_COLUMN] = $callId;

        return $variableData;
    }

    /**
     * Prepares data to be inserted in database.
     *
     * @param string $deliveryResultIdentifier
     * @param string $test
     * @param Variable $variable
     * @param string $callId
     *
     * @return array
     */
    protected function prepareTestVariableData($deliveryResultIdentifier, $test, Variable $variable, $callId)
    {
        $variableData = $this->prepareVariableData($deliveryResultIdentifier, $test, $variable, $callId);
        $variableData[self::CALL_ID_TEST_COLUMN] = $callId;

        return $variableData;
    }

    /**
     * Prepares data to be inserted in database.
     *
     * @param string $deliveryResultIdentifier
     * @param string $test
     * @param Variable $variable
     * @param string $callId
     *
     * @return array
     */
    protected function prepareVariableData($deliveryResultIdentifier, $test, Variable $variable, $callId)
    {
        // Ensures that variable has epoch.
        if (!$variable->isSetEpoch()) {
            $variable->setEpoch(microtime());
        }

        return $this->prepareVariableDataForSchema($deliveryResultIdentifier, $test, $variable, $callId);
    }

    /**
     * Prepares data to be inserted in database according to a given schema.
     *
     * @param string $deliveryResultIdentifier
     * @param string $test
     * @param Variable $variable
     * @param string $callId
     *
     * @return array
     */
    abstract protected function prepareVariableDataForSchema($deliveryResultIdentifier, $test, Variable $variable, $callId);

    /**
     * Builds a variable from database row.
     *
     * @param array $variable
     *
     * @return \stdClass
     */
    protected function getResultRow($variable)
    {
        $resultVariable = $this->unserializeVariableValue($variable[self::VARIABLE_VALUE]);
        $object = new \stdClass();
        $object->uri = $variable[self::VARIABLES_TABLE_ID];
        $object->class = get_class($resultVariable);
        $object->deliveryResultIdentifier = $variable[self::VARIABLES_FK_COLUMN];
        $object->callIdItem = $variable[self::CALL_ID_ITEM_COLUMN];
        $object->callIdTest = $variable[self::CALL_ID_TEST_COLUMN];
        $object->test = $variable[self::TEST_COLUMN];
        $object->item = $variable[self::ITEM_COLUMN];
        $object->variable = clone $resultVariable;

        return $object;
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatform()->getQueryBuilder();
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    public function getPersistence()
    {
        if ($this->persistence === null) {
            $persistenceId = $this->hasOption(self::OPTION_PERSISTENCE) ?
                $this->getOption(self::OPTION_PERSISTENCE)
                : 'default';
            $this->persistence = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID)->getPersistenceById($persistenceId);
        }

        return $this->persistence;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    protected function unserializeVariableValue($value)
    {
        $unserializedValue = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return Variable::fromData($unserializedValue);
        }

        return unserialize(
            $value,
            [
                'allowed_classes' => [
                    \taoResultServer_models_classes_ResponseVariable::class,
                    \taoResultServer_models_classes_OutcomeVariable::class,
                    \taoResultServer_models_classes_TraceVariable::class,
                ],
            ]
        );
    }

    /**
     * @param $value
     *
     * @return string
     */
    protected function serializeVariableValue($value)
    {
        if (!$value instanceof \taoResultServer_models_classes_Variable) {
            throw new \LogicException(
                sprintf(
                    "Value cannot be serialized. Expected instance of '%s', '%s' received.",
                    \taoResultServer_models_classes_Variable::class,
                    gettype($value)
                )
            );
        }

        return json_encode($value);
    }

    /**
     * @param array $data
     *
     * @param array $types
     * @throws DuplicateVariableException
     */
    private function insertMultiple(array $data, array $types = [])
    {
        if (empty($types)) {
            $types = $this->getTypes($data);
        }
        $duplicatedData = false;
        try {
            $this->getPersistence()->insertMultiple(self::VARIABLES_TABLENAME, $data, $types);
        } catch (UniqueConstraintViolationException $e) {
            $duplicatedData = true;
            foreach ($data as $row) {
                try {
                    $this->getPersistence()->insert(self::VARIABLES_TABLENAME, $row, $types);
                } catch (UniqueConstraintViolationException $e) {
                    //do nothing, just skip it
                }
            }
        }

        if ($duplicatedData) {
            throw new DuplicateVariableException(sprintf('An identical result variable already exists.'));
        }
    }

    public function spawnResult()
    {
        $this->getLogger()->error('Unsupported function');
    }

    /*
     * retrieve specific parameters from the resultserver to configure the storage
     */
    public function configure($callOptions = [])
    {
        $this->getLogger()->info('configure  RdsResultStorage with options : ' . implode(' ', $callOptions));
    }

    /**
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return number
     */
    public static function sortTimeStamps($a, $b)
    {
        [$usec, $sec] = explode(' ', $a);
        $floata = ((float)$usec + (float)$sec);
        [$usec, $sec] = explode(' ', $b);
        $floatb = ((float)$usec + (float)$sec);
        //the callback is expecting an int returned, for the case where the difference is of less than a second
        if ((floatval($floata) - floatval($floatb)) > 0) {
            return 1;
        } elseif ((floatval($floata) - floatval($floatb)) < 0) {
            return -1;
        } else {
            return 0;
        }
    }

    /**
     * Creates the table for results storage.
     *
     * @param Schema $schema
     *
     * @return Table
     * @throws SchemaException
     */
    public function createResultsTable(Schema $schema)
    {
        $table = $schema->createtable(self::RESULTS_TABLENAME);
        $table->addOption('engine', 'MyISAM');

        $table->addColumn(self::RESULTS_TABLE_ID, 'string', ['length' => 255]);
        $table->addColumn(self::TEST_TAKER_COLUMN, 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(self::DELIVERY_COLUMN, 'string', ['notnull' => false, 'length' => 255]);

        $table->setPrimaryKey([self::RESULTS_TABLE_ID]);

        return $table;
    }

    /**
     * Creates the table for variables storage.
     *
     * @param Schema $schema
     *
     * @return Table
     * @throws SchemaException
     */
    abstract public function createVariablesTable(Schema $schema);

    /**
     * Adds constraints for the tables.
     *
     * @param Table $variablesTable
     * @param Table $resultsTable
     *
     * @throws SchemaException
     */
    public function createTableConstraints(Table $variablesTable, Table $resultsTable)
    {
        $variablesTable->addForeignKeyConstraint(
            $resultsTable,
            [self::VARIABLES_FK_COLUMN],
            [self::RESULTS_TABLE_ID],
            [],
            self::VARIABLES_FK_NAME
        );
    }
    protected function getTypes(array $data = []): array
    {
        return [
            ParameterType::STRING,
            ParameterType::STRING,
            ParameterType::STRING,
            ParameterType::STRING,
            ParameterType::STRING,
            ParameterType::STRING,
            null,
            ParameterType::STRING,
        ];
    }
}

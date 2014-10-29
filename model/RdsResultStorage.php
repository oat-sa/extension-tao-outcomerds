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
 */

namespace oat\taoOutcomeRds\model;

use oat\taoResultServer\models\classes\ResultManagement;

/**
 * Implements tao results storage using the configured persistency "taoOutcomeRds"
 *
 */
class RdsResultStorage extends \tao_models_classes_GenerisService
    implements \taoResultServer_models_classes_WritableResultStorage, \taoResultServer_models_classes_ReadableResultStorage, ResultManagement
{
    /**
     * Constantes for the database creation and data access
     *
     */
    const RESULTS_TABLENAME = "results_storage";
    const RESULTS_TABLE_ID = 'result_id';
    const TEST_TAKER_COLUMN = 'test_taker';
    const DELIVERY_COLUMN = 'delivery';


    const VARIABLES_TABLENAME = "variables_storage";
    const VARIABLES_TABLE_ID = "variable_id";
    const CALL_ID_ITEM_COLUMN = "call_id_item";
    const CALL_ID_TEST_COLUMN = "call_id_test";
    const TEST_COLUMN = "test";
    const ITEM_COLUMN = "item";
    const VARIABLE_IDENTIFIER = "identifier";
    const VARIABLE_CLASS = "class";
    const VARIABLES_FK_COLUMN = "results_result_id";
    const VARIABLES_FK_NAME = "fk_variables_results";

    const RESULT_KEY_VALUE_TABLE_NAME = "results_kv_storage";
    const KEY_COLUMN = "result_key";
    const VALUE_COLUMN = "result_value";
    const RESULTSKV_FK_COLUMN = "variables_variable_id";
    const RESULTSKV_FK_NAME = "fk_resultsKv_variables";


    /**
     * SQL persistence to use
     *
     * @var common_persistence_SqlPersistence
     */
    private $persistence;

    public function __construct()
    {
        parent::__construct();
        $this->persistence = $this->getPersistence();
    }

    private function getPersistence()
    {
        return \common_persistence_Manager::getPersistence('default');
    }

    /**
     * Store in the table all value corresponding to a key
     * @param \taoResultServer_models_classes_Variable $variable
     */
    private function storeKeysValues($variableId, \taoResultServer_models_classes_Variable $variable)
    {
        $basetype = $variable->getBaseType();
        foreach (array_keys((array)$variable) as $key) {
            $getter = 'get' . ucfirst($key);
            $value = null;
            if (method_exists($variable, $getter)) {
                $value = $variable->$getter();
                if ($key == 'value' || $key == 'candidateResponse') {
                    $value = base64_encode($value);
                }
            }
            if ($key == 'epoch' && !$variable->isSetEpoch()) {
                $value = microtime();
            }
            $this->persistence->insert(
                self::RESULT_KEY_VALUE_TABLE_NAME,
                array(
                    self::RESULTSKV_FK_COLUMN => $variableId,
                    self::KEY_COLUMN => $key,
                    self::VALUE_COLUMN => $value
                )
            );
        }
    }

    public function spawnResult()
    {
        \common_Logger::w('Unsupported function');
    }

    /**
     * Store the test variable in table and its value in key/value storage
     *
     * @param type $deliveryResultIdentifier
     *            lis_result_sourcedid
     * @param type $test
     *            ignored
     * @param \taoResultServer_models_classes_Variable $testVariable
     * @param type $callIdTest
     *            ignored
     */
    public function storeTestVariable(
        $deliveryResultIdentifier,
        $test,
        \taoResultServer_models_classes_Variable $testVariable,
        $callIdTest
    ) {
        $sql = 'SELECT COUNT(*) FROM ' . self::VARIABLES_TABLENAME .
            ' WHERE ' . self::VARIABLES_FK_COLUMN . ' = ? AND ' . self::TEST_COLUMN . ' = ?
            AND ' . self::VARIABLE_IDENTIFIER . ' = ?';
        $params = array($deliveryResultIdentifier, $test, $testVariable->getIdentifier());

        // if there is already a record for this item we update it
        if ($this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0] > 0) {
            $sqlUpdate = 'UPDATE ' . self::VARIABLES_TABLENAME . ' SET ' . self::CALL_ID_TEST_COLUMN . ' = ?
            WHERE ' . self::VARIABLES_FK_COLUMN . ' = ? AND ' . self::TEST_COLUMN . ' = ? AND ' . self::VARIABLE_IDENTIFIER . ' = ?';
            $paramsUpdate = array($callIdTest, $deliveryResultIdentifier, $test, $testVariable->getIdentifier());
            $this->persistence->exec($sqlUpdate, $paramsUpdate);
        } else {
            $variableClass = get_class($testVariable);

            $this->persistence->insert(
                self::VARIABLES_TABLENAME,
                array(
                    self::VARIABLES_FK_COLUMN => $deliveryResultIdentifier,
                    self::TEST_COLUMN => $test,
                    self::CALL_ID_TEST_COLUMN => $callIdTest,
                    self::VARIABLE_CLASS => $variableClass,
                    self::VARIABLE_IDENTIFIER => $testVariable->getIdentifier()
                )
            );

            $variableId = $this->persistence->lastInsertId();
            $this->storeKeysValues($variableId, $testVariable);
        }
    }

    /**
     * Store the item in table and its value in key/value storage
     * @param $deliveryResultIdentifier
     * @param $test
     * @param $item
     * @param \taoResultServer_models_classes_Variable $itemVariable
     * @param $callIdItem
     */
    public function storeItemVariable(
        $deliveryResultIdentifier,
        $test,
        $item,
        \taoResultServer_models_classes_Variable $itemVariable,
        $callIdItem
    ) {

        $sql = 'SELECT COUNT(*) FROM ' . self::VARIABLES_TABLENAME .
            ' WHERE ' . self::VARIABLES_FK_COLUMN . ' = ? AND ' . self::TEST_COLUMN . ' = ?
            AND ' . self::CALL_ID_ITEM_COLUMN . ' = ? AND ' . self::VARIABLE_IDENTIFIER . ' = ?';
        $params = array($deliveryResultIdentifier, $test, $callIdItem, $itemVariable->getIdentifier());

        // if there is already a record for this item we skip saving
        if ($this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0] == 0) {

            $variableClass = get_class($itemVariable);

            $this->persistence->insert(
                self::VARIABLES_TABLENAME,
                array(
                    self::VARIABLES_FK_COLUMN => $deliveryResultIdentifier,
                    self::TEST_COLUMN => $test,
                    self::ITEM_COLUMN => $item,
                    self::CALL_ID_ITEM_COLUMN => $callIdItem,
                    self::VARIABLE_CLASS => $variableClass,
                    self::VARIABLE_IDENTIFIER => $itemVariable->getIdentifier()
                )
            );

            $variableId = $this->persistence->lastInsertId();

            $this->storeKeysValues($variableId, $itemVariable);
        }


    }

    /*
     * retrieve specific parameters from the resultserver to configure the storage
     */
    public function configure(\core_kernel_classes_Resource $resultserver, $callOptions = array())
    {
        \common_Logger::w('configure : ' . implode(" ", $callOptions));
    }

    /**
     * Store test-taker doing the test
     * @param $deliveryResultIdentifier
     * @param $testTakerIdentifier
     */
    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier)
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::RESULTS_TABLENAME .
            ' WHERE ' . self::RESULTS_TABLE_ID . ' = ?';
        $params = array($deliveryResultIdentifier);
        if ($this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0] == 0) {
            $this->persistence->insert(
                self::RESULTS_TABLENAME,
                array(
                    self::TEST_TAKER_COLUMN => $testTakerIdentifier,
                    self::RESULTS_TABLE_ID => $deliveryResultIdentifier
                )
            );
        } else {
            $sqlUpdate = 'UPDATE ' . self::RESULTS_TABLENAME . ' SET ' . self::TEST_TAKER_COLUMN . ' = ? WHERE ' . self::RESULTS_TABLE_ID . ' = ?';
            $paramsUpdate = array($testTakerIdentifier, $deliveryResultIdentifier);
            $this->persistence->exec($sqlUpdate, $paramsUpdate);
        }
    }

    /**
     * Store Delivery corresponding to the current test
     * @param $deliveryResultIdentifier
     * @param $deliveryIdentifier
     */
    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier)
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::RESULTS_TABLENAME .
            ' WHERE ' . self::RESULTS_TABLE_ID . ' = ?';
        $params = array($deliveryResultIdentifier);
        if ($this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0] == 0) {
            $this->persistence->insert(
                self::RESULTS_TABLENAME,
                array(self::DELIVERY_COLUMN => $deliveryIdentifier, self::RESULTS_TABLE_ID => $deliveryResultIdentifier)
            );
        } else {
            $sqlUpdate = 'UPDATE ' . self::RESULTS_TABLENAME . ' SET ' . self::DELIVERY_COLUMN . ' = ? WHERE ' . self::RESULTS_TABLE_ID . ' = ?';
            $paramsUpdate = array($deliveryIdentifier, $deliveryResultIdentifier);
            $this->persistence->exec($sqlUpdate, $paramsUpdate);
        }
    }

    /**
     * @param callId an item execution identifier
     * @return array keys as variableIdentifier , values is an array of observations ,
     * each observation is an object with deliveryResultIdentifier, test, taoResultServer_models_classes_Variable variable, callIdTest
     * Array
     * (
     * [LtiOutcome] => Array
     * (
     * [0] => stdClass Object
     * (
     * [deliveryResultIdentifier] => con-777:::rlid-777:::777777
     * [test] => http://tao26/tao26.rdf#i1402389674744647
     * [item] => http://tao26/tao26.rdf#i1402334674744890
     * [variable] => taoResultServer_models_classes_OutcomeVariable Object
     * (
     * [normalMaximum] =>
     * [normalMinimum] =>
     * [value] => MC41
     * [identifier] => LtiOutcome
     * [cardinality] => single
     * [baseType] => float
     * [epoch] => 0.10037600 1402390997
     * )
     * [callIdTest] => http://tao26/tao26.rdf#i14023907995907103
     * [callIdItem] => http://tao26/tao26.rdf#i14023907995907110
     * )
     *
     * )
     *
     * )
     */
    public function getVariables($callId)
    {
        $sql = 'SELECT * FROM ' . self::VARIABLES_TABLENAME . ', ' . self::RESULT_KEY_VALUE_TABLE_NAME . '
        WHERE (' . self::CALL_ID_ITEM_COLUMN . ' = ? OR ' . self::CALL_ID_TEST_COLUMN . ' = ?) AND ' . self::VARIABLES_TABLE_ID . ' = ' . self::RESULTSKV_FK_COLUMN;
        $params = array($callId, $callId);
        $variables = $this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $returnValue = array();

        // for each variable we construct the array
        $lastVariable = array();


        foreach ($variables as $variable) {

            if (empty($lastVariable)) {
                $lastVariable = $variable;
                if (class_exists($lastVariable[self::VARIABLE_CLASS])) {
                    $resultVariable = new $lastVariable[self::VARIABLE_CLASS]();
                } else {
                    $resultVariable = new \taoResultServer_models_classes_OutcomeVariable();
                }
            }

            // store variable from 0 to n-1
            if ($lastVariable[self::VARIABLES_TABLE_ID] != $variable[self::VARIABLES_TABLE_ID]) {
                $object = new \stdClass();
                $object->uri = $lastVariable[self::VARIABLES_TABLE_ID];
                $object->class = $lastVariable[self::VARIABLE_CLASS];
                $object->deliveryResultIdentifier = $lastVariable[self::VARIABLES_FK_COLUMN];
                $object->callIdItem = $lastVariable[self::CALL_ID_ITEM_COLUMN];
                $object->callIdTest = $lastVariable[self::CALL_ID_TEST_COLUMN];
                $object->test = $lastVariable[self::TEST_COLUMN];
                $object->item = $lastVariable[self::ITEM_COLUMN];
                $object->variable = clone $resultVariable;
                $returnValue[$lastVariable[self::VARIABLES_TABLE_ID]][] = $object;
                $lastVariable = $variable;
                if (class_exists($lastVariable[self::VARIABLE_CLASS])) {
                    $resultVariable = new $lastVariable[self::VARIABLE_CLASS]();
                } else {
                    $resultVariable = new \taoResultServer_models_classes_OutcomeVariable();
                }
            }

            $setter = 'set' . ucfirst($variable[self::KEY_COLUMN]);

            if (method_exists($resultVariable, $setter)) {
                $value = $variable[self::VALUE_COLUMN];
                if ($variable[self::KEY_COLUMN] == 'value' || $variable[self::KEY_COLUMN] == 'candidateResponse') {
                    $value = base64_decode($value);
                }

                $resultVariable->$setter($value);
            }

        }
        if (count($variables) > 0) {
            // store the variable n
            $object = new \stdClass();
            $object->uri = $lastVariable[self::VARIABLES_TABLE_ID];
            $object->class = $lastVariable[self::VARIABLE_CLASS];
            $object->deliveryResultIdentifier = $lastVariable[self::VARIABLES_FK_COLUMN];
            $object->callIdItem = $lastVariable[self::CALL_ID_ITEM_COLUMN];
            $object->callIdTest = $lastVariable[self::CALL_ID_TEST_COLUMN];
            $object->test = $lastVariable[self::TEST_COLUMN];
            $object->item = $lastVariable[self::ITEM_COLUMN];
            $object->variable = clone $resultVariable;
            $returnValue[$lastVariable[self::VARIABLES_TABLE_ID]][] = $object;
        }

        return $returnValue;
    }

    /**
     * Get a variable from callId and Variable identifier
     * @param $callId
     * @param $variableIdentifier
     * @return array
     */
    public function getVariable($callId, $variableIdentifier)
    {
        $sql = 'SELECT * FROM ' . self::VARIABLES_TABLENAME . ', ' . self::RESULT_KEY_VALUE_TABLE_NAME . '
        WHERE (' . self::CALL_ID_ITEM_COLUMN . ' = ? OR ' . self::CALL_ID_TEST_COLUMN . ' = ?)
        AND ' . self::VARIABLES_TABLE_ID . ' = ' . self::RESULTSKV_FK_COLUMN . ' AND ' . self::VARIABLE_IDENTIFIER . ' = ?';

        $params = array($callId, $callId, $variableIdentifier);
        $variables = $this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $returnValue = array();

        // for each variable we construct the array
        $lastVariable = array();
        foreach ($variables as $variable) {
            if (empty($lastVariable)) {
                $lastVariable = $variable;
                if (class_exists($lastVariable[self::VARIABLE_CLASS])) {
                    $resultVariable = new $lastVariable[self::VARIABLE_CLASS]();
                } else {
                    $resultVariable = new \taoResultServer_models_classes_OutcomeVariable();
                }
            }
            if ($lastVariable[self::VARIABLES_TABLE_ID] != $variable[self::VARIABLES_TABLE_ID]) {
                $object = new \stdClass();
                $object->uri = $lastVariable[self::VARIABLES_TABLE_ID];
                $object->class = $lastVariable[self::VARIABLE_CLASS];
                $object->deliveryResultIdentifier = $lastVariable[self::VARIABLES_FK_COLUMN];
                $object->callIdItem = $lastVariable[self::CALL_ID_ITEM_COLUMN];
                $object->callIdTest = $lastVariable[self::CALL_ID_TEST_COLUMN];
                $object->test = $lastVariable[self::TEST_COLUMN];
                $object->item = $lastVariable[self::ITEM_COLUMN];
                $object->variable = clone $resultVariable;
                $returnValue[$lastVariable[self::VARIABLES_TABLE_ID]][] = $object;

                $lastVariable = $variable;
                if (class_exists($lastVariable[self::VARIABLE_CLASS])) {
                    $resultVariable = new $lastVariable[self::VARIABLE_CLASS]();
                } else {
                    $resultVariable = new \taoResultServer_models_classes_OutcomeVariable();
                }
            }

            $setter = 'set' . ucfirst($variable[self::KEY_COLUMN]);
            if (method_exists($resultVariable, $setter)) {
                $value = $variable[self::VALUE_COLUMN];
                if ($variable[self::KEY_COLUMN] == 'value' || $variable[self::KEY_COLUMN] == 'candidateResponse') {
                    $value = base64_decode($value);
                }

                $resultVariable->$setter($value);
            }

        }

        // store the variable n
        if (count($variables) > 0) {
            $object = new \stdClass();
            $object->uri = $lastVariable[self::VARIABLES_TABLE_ID];
            $object->class = $lastVariable[self::VARIABLE_CLASS];
            $object->deliveryResultIdentifier = $lastVariable[self::VARIABLES_FK_COLUMN];
            $object->callIdItem = $lastVariable[self::CALL_ID_ITEM_COLUMN];
            $object->callIdTest = $lastVariable[self::CALL_ID_TEST_COLUMN];
            $object->test = $lastVariable[self::TEST_COLUMN];
            $object->item = $lastVariable[self::ITEM_COLUMN];
            $object->variable = clone $resultVariable;
            $returnValue[$lastVariable[self::VARIABLES_TABLE_ID]][] = $object;
        }
        return $returnValue;

    }

    public function getVariableProperty($variableId, $property)
    {
        $sql = 'SELECT ' . self::VALUE_COLUMN . ' FROM ' . self::RESULT_KEY_VALUE_TABLE_NAME . '
        WHERE ' . self::RESULTSKV_FK_COLUMN . ' = ? AND ' . self::KEY_COLUMN . ' = ?';
        $params = array($variableId, $property);
        return $this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0];

    }

    /**
     * get test-taker corresponding to a result
     * @param $deliveryResultIdentifier
     * @return mixed
     */
    public function getTestTaker($deliveryResultIdentifier)
    {
        $sql = 'SELECT ' . self::TEST_TAKER_COLUMN . ' FROM ' . self::RESULTS_TABLENAME . ' WHERE ' . self::RESULTS_TABLE_ID . ' = ?';
        $params = array($deliveryResultIdentifier);
        return $this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0];
    }

    /**
     * get delivery corresponding to a result
     * @param $deliveryResultIdentifier
     * @return mixed
     */
    public function getDelivery($deliveryResultIdentifier)
    {
        $sql = 'SELECT ' . self::DELIVERY_COLUMN . ' FROM ' . self::RESULTS_TABLENAME . ' WHERE ' . self::RESULTS_TABLE_ID . ' = ?';
        $params = array($deliveryResultIdentifier);
        return $this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0];
    }

    /**
     * @return array the list of item executions ids (across all results)
     * o(n) do not use real time (postprocessing)
     */
    public function getAllCallIds()
    {
        $returnValue = array();
        $sql = 'SELECT DISTINCT(' . self::CALL_ID_ITEM_COLUMN . '), ' . self::CALL_ID_TEST_COLUMN . ', ' . self::VARIABLES_FK_COLUMN . ' FROM ' . self::VARIABLES_TABLENAME;
        foreach ($this->persistence->query($sql)->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            $returnValue[] = ($value[self::CALL_ID_ITEM_COLUMN] != "") ? $value[self::CALL_ID_ITEM_COLUMN] : $value[self::CALL_ID_TEST_COLUMN];
        }

        return $returnValue;
    }

    /**
     * @param $deliveryResultIdentifier
     * @return array the list of item executions ids related to a delivery result
     */
    public function getRelatedItemCallIds($deliveryResultIdentifier)
    {
        $returnValue = array();
        $sql = 'SELECT DISTINCT(' . self::CALL_ID_ITEM_COLUMN . ') FROM ' . self::VARIABLES_TABLENAME . '
        WHERE ' . self::VARIABLES_FK_COLUMN . ' = ? AND ' . self::CALL_ID_ITEM_COLUMN . ' <> \'\'';
        $params = array($deliveryResultIdentifier);
        foreach ($this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            $returnValue[] = $value[self::CALL_ID_ITEM_COLUMN];
        }

        return $returnValue;
    }

    /**
     * (non-PHPdoc)
     * @see taoResultServer_models_classes_ReadableResultStorage::getAllTestTakerIds()
     */
    public function getAllTestTakerIds()
    {
        $returnValue = array();
        $sql = 'SELECT ' . self::RESULTS_TABLE_ID . ', ' . self::TEST_TAKER_COLUMN . ' FROM ' . self::RESULTS_TABLENAME;
        foreach ($this->persistence->query($sql)->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            $returnValue[] = array(
                "deliveryResultIdentifier" => $value[self::RESULTS_TABLE_ID],
                "testTakerIdentifier" => $value[self::TEST_TAKER_COLUMN]
            );
        }
        return $returnValue;
    }

    /**
     * (non-PHPdoc)
     * @see taoResultServer_models_classes_ReadableResultStorage::getAllDeliveryIds()
     */
    public function getAllDeliveryIds()
    {
        $returnValue = array();
        $sql = 'SELECT ' . self::RESULTS_TABLE_ID . ', ' . self::DELIVERY_COLUMN . ' FROM ' . self::RESULTS_TABLENAME;
        foreach ($this->persistence->query($sql)->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            $returnValue[] = array(
                "deliveryResultIdentifier" => $value[self::RESULTS_TABLE_ID],
                "deliveryIdentifier" => $value[self::DELIVERY_COLUMN]
            );
        }
        return $returnValue;
    }

    /**
     * order orderdir, offset, limit
     */
    public function getResultByColumn($columns, $filter, $options)
    {
        $returnValue = array();
        $sql = 'SELECT * FROM ' . self::RESULTS_TABLENAME;
        $params = array();


        if (count($columns) > 0) {
            $sql .= ' WHERE ';
        }

        if (in_array(PROPERTY_RESULT_OF_DELIVERY, $columns)) {
            $inQuery = implode(',', array_fill(0, count($filter[PROPERTY_RESULT_OF_DELIVERY]), '?'));
            $sql .= self::DELIVERY_COLUMN . ' IN (' . $inQuery . ')';
            $params = array_merge($params, $filter[PROPERTY_RESULT_OF_DELIVERY]);
        }

        if (count($columns) > 1) {
            $sql .= ' AND ';
        }

        if (in_array(PROPERTY_RESULT_OF_SUBJECT, $columns)) {
            $inQuery = implode(',', array_fill(0, count($filter[PROPERTY_RESULT_OF_SUBJECT]), '?'));
            $sql .= self::TEST_TAKER_COLUMN . ' IN (' . $inQuery . ')';
            $params = array_merge($params, $filter[PROPERTY_RESULT_OF_SUBJECT]);
        }

        if(isset($options['order'])){
            $sql .= ' ORDER BY ?';
            $params = array_merge($params, $options['order']);
            if(isset($options['oderdir'])){
                $sql .= ' ?';
                $params = array_merge($params, $options['orderdir']);
            }
        }
        if(isset($options['offset']) || isset($options['limit'])){
            $offset = (isset($options['offset']))?$options['offset']:0;
            $limit = (isset($options['limit']))?$options['limit']:1000;
            $sql .= ' LIMIT ?,?';
            $params = array_merge($params, array($offset, $limit));
        }

        foreach ($this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            $returnValue[] = array(
                "deliveryResultIdentifier" => $value[self::RESULTS_TABLE_ID],
                "testTakerIdentifier" => $value[self::TEST_TAKER_COLUMN],
                "deliveryIdentifier" => $value[self::DELIVERY_COLUMN]
            );
        }
        return $returnValue;

    }

    public function countResultByFilter($columns, $filter){
        $returnValue = array();
        $sql = 'SELECT COUNT(*) FROM ' . self::RESULTS_TABLENAME;
        $params = array();


        if (count($columns) > 0) {
            $sql .= ' WHERE ';
        }

        if (in_array(PROPERTY_RESULT_OF_DELIVERY, $columns)) {
            $inQuery = implode(',', array_fill(0, count($filter[PROPERTY_RESULT_OF_DELIVERY]), '?'));
            $sql .= self::DELIVERY_COLUMN . ' IN (' . $inQuery . ')';
            $params = array_merge($params, $filter[PROPERTY_RESULT_OF_DELIVERY]);
        }

        if (count($columns) > 1) {
            $sql .= ' AND ';
        }

        if (in_array(PROPERTY_RESULT_OF_SUBJECT, $columns)) {
            $inQuery = implode(',', array_fill(0, count($filter[PROPERTY_RESULT_OF_SUBJECT]), '?'));
            $sql .= self::TEST_TAKER_COLUMN . ' IN (' . $inQuery . ')';
            $params = array_merge($params, $filter[PROPERTY_RESULT_OF_SUBJECT]);
        }

        return $this->persistence->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN)[0];
    }


    /**
     * Remove the result and all the related variables
     * @param $deliveryResultIdentifier
     * @return bool
     */
    public function deleteResult($deliveryResultIdentifier)
    {
        // get all the variables related to the result
        $sql = 'SELECT ' . self::VARIABLES_TABLE_ID . ' FROM ' . self::VARIABLES_TABLENAME . '
        WHERE ' . self::VARIABLES_FK_COLUMN . ' = ?';
        $variables = $this->persistence->query($sql, array($deliveryResultIdentifier))->fetchAll(\PDO::FETCH_ASSOC);

        // delete key/value for each variable
        foreach ($variables as $variable) {
            $sql = 'DELETE FROM ' . self::RESULT_KEY_VALUE_TABLE_NAME . '
            WHERE ' . self::RESULTSKV_FK_COLUMN . ' = ?';

            if ($this->persistence->exec($sql, array($variable[self::VARIABLES_TABLE_ID])) === false) {
                return false;
            }
        }

        // remove variables
        $sql = 'DELETE FROM ' . self::VARIABLES_TABLENAME . '
            WHERE ' . self::VARIABLES_FK_COLUMN . ' = ?';

        if ($this->persistence->exec($sql, array($deliveryResultIdentifier)) === false) {
            return false;
        }

        // remove results
        $sql = 'DELETE FROM ' . self::RESULTS_TABLENAME . '
            WHERE ' . self::RESULTS_TABLE_ID . ' = ?';

        if ($this->persistence->exec($sql, array($deliveryResultIdentifier)) === false) {
            return false;
        }

        return true;
    }
}

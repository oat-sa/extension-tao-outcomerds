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

/**
 * Implements tao results storage using the configured persistency "taoOutcomeRds"
 *
 */
class taoOutcomeRds_models_classes_RdsResultStorage extends tao_models_classes_GenerisService
    implements taoResultServer_models_classes_WritableResultStorage, taoResultServer_models_classes_ReadableResultStorage
{
    const RESULTS_TABLENAME = "results_storage";
    const RESULTS_TABLE_ID = 'result_id';
    const TEST_TAKER_COLUMN = 'test_taker';
    const DELIVERY_COLUMN = 'delivery';


    const VARIABLES_TABLENAME = "variables_storage";
    const VARIABLES_TABLE_ID = "variable_id";
    const CALL_ID_TEST_COLUMN = "call_id_test";
    const CALL_ID_ITEM_COLUMN = "call_id_item";
    const TEST_COLUMN = "test";
    const VARIABLES_FK_COLUMN = "results_result_id";
    const VARIABLES_FK_NAME = "fk_variables_results";

    const RESULT_KEY_VALUE_TABLE_NAME = "results_kv_storage";
    const KEY_COLUMN = "result_key";
    const VALUE_COLUMN = "result_value";
    const RESULTSKV_FK_COLUMN = "variables_variable_id";
    const RESULTSKV_FK_NAME = "fk_resultsKv_variables";


    static $PrefixResultsId = "taoRdsResultStorage:id";


    private $persistence;
    private $lastInsertId;

    public function __construct()
    {
        parent::__construct();
        $this->persistence = $this->getPersistence();
    }

    private function getPersistence()
    {
        $this->persistence = common_persistence_Manager::getPersistence('default');
        return $this->persistence;
    }

    private function storeKeysValues(taoResultServer_models_classes_Variable $variable){
        foreach((array)$variable as $key => $value){
            try{

                $this->persistence->insert(
                    self::RESULT_KEY_VALUE_TABLE_NAME,
                    array(self::RESULTSKV_FK_COLUMN => $this->lastInsertId,
                        self::KEY_COLUMN => $key,
                        self::VALUE_COLUMN => $value)
                );
            }
            catch(PDOException $e){
                var_dump(array(self::RESULTSKV_FK_COLUMN => $this->lastInsertId,
                        self::KEY_COLUMN => $key,
                        self::VALUE_COLUMN => $value));
                echo 'STORE : '.$e->getMessage();
            }
        }
    }

    public function spawnResult(){

    }   
    
    /**
     *
     * @param type $deliveryResultIdentifier
     *            lis_result_sourcedid
     * @param type $test
     *            ignored
     * @param taoResultServer_models_classes_Variable $testVariable            
     * @param type $callIdTest
     *            ignored
     */
    public function storeTestVariable($deliveryResultIdentifier, $test, taoResultServer_models_classes_Variable $testVariable, $callIdTest)
    {

        $sqlUpdate = 'UPDATE ' .self::VARIABLES_TABLENAME. ' SET ' .self::CALL_ID_TEST_COLUMN. ' = ?
        WHERE ' .self::VARIABLES_TABLE_ID. ' = ?';
        $paramsUpdate = array($callIdTest, $this->lastInsertId);
        $this->persistence->exec($sqlUpdate,$paramsUpdate);

        // In all case we add the key values
        $this->storeKeysValues($testVariable);

    }

    public function storeItemVariable($deliveryResultIdentifier, $test, $item, taoResultServer_models_classes_Variable $itemVariable, $callIdItem)
    {

         $this->persistence->insert(self::VARIABLES_TABLENAME,
            array(self::VARIABLES_FK_COLUMN => $deliveryResultIdentifier,
                self::TEST_COLUMN => $test,
                self::CALL_ID_ITEM_COLUMN => $callIdItem));

        $this->lastInsertId = $this->persistence->lastInsertId();

        // In all case we add the key values
        $this->storeKeysValues($itemVariable);


    }

    /*
     * retrieve specific parameters from the resultserver to configure the storage
     */
    /*sic*/
    public function configure(core_kernel_classes_Resource $resultserver, $callOptions = array())
    {

    }

    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier)
    {
        $sql = 'SELECT COUNT(*) FROM ' .self::RESULTS_TABLENAME.
            ' WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
        $params = array($deliveryResultIdentifier);
        if($this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_COLUMN)[0] == 0){
            $this->persistence->insert(self::RESULTS_TABLENAME,
                array(self::TEST_TAKER_COLUMN => $testTakerIdentifier, self::RESULTS_TABLE_ID => $deliveryResultIdentifier));
        }
        else{
            $sqlUpdate = 'UPDATE ' .self::RESULTS_TABLENAME. ' SET ' .self::TEST_TAKER_COLUMN. ' = ? WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
            $paramsUpdate = array($testTakerIdentifier, $deliveryResultIdentifier);
            $this->persistence->exec($sqlUpdate,$paramsUpdate);
        }
    }

    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier)
    {
        $sql = 'SELECT COUNT(*) FROM ' .self::RESULTS_TABLENAME.
            ' WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
        $params = array($deliveryResultIdentifier);
        if($this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_COLUMN)[0] == 0){
            $this->persistence->insert(self::RESULTS_TABLENAME,
                array(self::DELIVERY_COLUMN => $deliveryIdentifier, self::RESULTS_TABLE_ID => $deliveryResultIdentifier));
        }
        else{
            $sqlUpdate = 'UPDATE ' .self::RESULTS_TABLENAME. ' SET ' .self::DELIVERY_COLUMN. ' = ? WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
            $paramsUpdate = array($deliveryIdentifier, $deliveryResultIdentifier);
            $this->persistence->exec($sqlUpdate,$paramsUpdate);
        }
    }



 /**
     * @param callId an item execution identifier
     * @return array keys as variableIdentifier , values is an array of observations , 
     * each observation is an object with deliveryResultIdentifier, test, taoResultServer_models_classes_Variable variable, callIdTest
     * Array
    (
    [LtiOutcome] => Array
        (
            [0] => stdClass Object
                (
                    [deliveryResultIdentifier] => con-777:::rlid-777:::777777
                    [test] => http://tao26/tao26.rdf#i1402389674744647
                    [variable] => taoResultServer_models_classes_OutcomeVariable Object
                        (
                            [normalMaximum] => 
                            [normalMinimum] => 
                            [value] => MC41
                            [identifier] => LtiOutcome
                            [cardinality] => single
                            [baseType] => float
                            [epoch] => 0.10037600 1402390997
                        )
                    [callIdTest] => http://tao26/tao26.rdf#i14023907995907103
                )

        )

    )
     */
    public function getVariables($callId)
    {
        $sql = 'SELECT * FROM ' .self::VARIABLES_TABLENAME. ' WHERE ' .self::CALL_ID_ITEM_COLUMN. ' = ?';
        $params = array($callId);

        $variables = $this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_ASSOC);

        $returnValue = array();

        // for each variable we construct the array

        foreach($variables as $variable){

            $variableId = $variable[self::VARIABLES_TABLE_ID];
            $returnValue[$variableId]['deliveryResultIdentifier'] = $variable[self::VARIABLES_FK_COLUMN];
            $returnValue[$variableId]['callIdTest'] = $variable[self::CALL_ID_TEST_COLUMN];

            $sql = 'SELECT ' .self::TEST_COLUMN. ' FROM ' .self::RESULTS_TABLENAME. ' WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
            $params = array($variable[self::VARIABLES_FK_COLUMN]);
            $test = $this->persistence->query($sql, $params)->fetchAll(PDO::FETCH_COLUMN)[0];
            $returnValue[$variableId]['test'] = $test;

            $sql = 'SELECT * FROM ' .self::RESULT_KEY_VALUE_TABLE_NAME. ' WHERE ' .self::VARIABLES_TABLE_ID. ' = ?';
            $params = array($variableId);
            $variableValues = $this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_ASSOC);

            foreach($variableValues as $value){
                $resultVariable = new taoResultServer_models_classes_OutcomeVariable();

                $setter = 'set'.ucfirst($value[self::KEY_COLUMN]);
                $resultVariable->$setter($value[self::VALUE_COLUMN]);

                $returnValue[$variableId]['variable'] = clone $resultVariable;
            }

        }

        return $returnValue;
    }

    public function getVariable($callId, $variableIdentifier)
    {
        $sql = 'SELECT * FROM ' .self::VARIABLES_TABLENAME. ' WHERE ' .self::CALL_ID_ITEM_COLUMN. ' = ? AND ' .self::VARIABLES_TABLE_ID. ' = ?';
        $params = array($callId,$variableIdentifier);
        $variables = $this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_ASSOC);

        $returnValue = array();

        // for each variable we construct the array

        foreach($variables as $variable){

            $variableId = $variable[self::VARIABLES_TABLE_ID];
            $returnValue[$variableId]['deliveryResultIdentifier'] = $variable[self::VARIABLES_FK_COLUMN];
            $returnValue[$variableId]['callIdTest'] = $variable[self::CALL_ID_TEST_COLUMN];

            $sql = 'SELECT ' .self::TEST_COLUMN. ' FROM ' .self::RESULTS_TABLENAME. ' WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
            $params = array($variable[self::VARIABLES_FK_COLUMN]);
            $test = $this->persistence->query($sql, $params)->fetchAll(PDO::FETCH_COLUMN)[0];
            $returnValue[$variableId]['test'] = $test;

            $sql = 'SELECT * FROM ' .self::RESULT_KEY_VALUE_TABLE_NAME. ' WHERE ' .self::VARIABLES_TABLE_ID. ' = ?';
            $params = array($variableId);
            $variableValues = $this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_ASSOC);

            foreach($variableValues as $value){
                $resultVariable = new taoResultServer_models_classes_OutcomeVariable();

                $setter = 'set'.ucfirst($value[self::KEY_COLUMN]);
                $resultVariable->$setter($value[self::VALUE_COLUMN]);

                $returnValue[$variableId]['variable'] = clone $resultVariable;
            }

        }

        return $returnValue;
        
    }

    public function getTestTaker($deliveryResultIdentifier)
    {
        $sql = 'SELECT ' .self::TEST_TAKER_COLUMN. ' FROM ' .self::RESULTS_TABLENAME. ' WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
        $params = array($deliveryResultIdentifier);
        return $this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_COLUMN)[0];
    }

    public function getDelivery($deliveryResultIdentifier)
    {
        $sql = 'SELECT ' .self::DELIVERY_COLUMN. ' FROM ' .self::RESULTS_TABLENAME. ' WHERE ' .self::RESULTS_TABLE_ID. ' = ?';
        $params = array($deliveryResultIdentifier);
        return $this->persistence->query($sql,$params)->fetchAll(PDO::FETCH_COLUMN)[0];
    }

    /**
     * @return array the list of item executions ids (across all results)
     * o(n) do not use real time (postprocessing)
     */

    public function getAllCallIds()
    {
        $sql = 'SELECT ' .self::CALL_ID_ITEM_COLUMN. ' FROM ' .self::VARIABLES_TABLENAME;
        return $this->persistence->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }
    /**
     * @return array each element is a two fields array deliveryResultIdentifier, testTakerIdentifier
     */
    public function getAllTestTakerIds()
    {
        $sql = 'SELECT ' .self::RESULTS_TABLE_ID. ', ' .self::TEST_TAKER_COLUMN. ' FROM ' .self::RESULTS_TABLENAME;
        return $this->persistence->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * @return array each element is a two fields array deliveryResultIdentifier, deliveryIdentifier
     */
    public function getAllDeliveryIds()
    {
        $sql = 'SELECT ' .self::RESULTS_TABLE_ID. ', ' .self::DELIVERY_COLUMN. ' FROM ' .self::RESULTS_TABLENAME;
        return $this->persistence->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

}

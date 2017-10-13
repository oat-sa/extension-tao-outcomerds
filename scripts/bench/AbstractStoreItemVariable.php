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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoOutcomeRds\scripts\bench;

use oat\oatbox\extension\AbstractAction;
use oat\taoOutcomeRds\model\RdsResultStorage;
use common_report_Report as Report;

abstract class AbstractStoreItemVariable extends AbstractAction
{
    /** @var  RdsResultStorage */
    protected $storage;
    
    public function __invoke($params)
    {
        // Arguments check.
        if (empty($params[0])) {
            return new Report(
                Report::TYPE_ERROR,
                "Argument 1 - Delivery Execution Count must be provided."
            );
        }
        
        if (empty($params[1])) {
            return new Report(
                Report::TYPE_ERROR,
                "Argument 2 - Attempt Count must be provided."
            );
        }
        
        
        $deliveryExecutionCount = intval($params[0]);
        $attemptCount = intval($params[1]);
        $purge = !empty($params[2]) && boolval($params[2]);
        
        $report = new Report(
            Report::TYPE_INFO,
            "The script ended gracefully."
        );
        
        $this->storage = $this->getServiceManager()->get(RdsResultStorage::SERVICE_ID);
        
        $time = [];
        
        for ($i = 0; $i < $deliveryExecutionCount; $i++) {
            
            $this->storage->storeRelatedTestTaker("deliveryResultIdentifier${i}", "testTakerIdentifier${i}");
            $this->storage->storeRelatedDelivery("deliveryResultIdentifier${i}", "deliveryIdentifier");
            
            for ($j = 0; $j < $attemptCount; $j++) {
                $itemVariables = [];
                
                $var = new \taoResultServer_models_classes_OutcomeVariable();
                $var->setIdentifier('SCORE');
                $var->setCardinality('single');
                $var->setBaseType('float');
                $var->setEpoch(microtime());
                $var->setValue('1');
                $var->setNormalMinimum(0);
                $var->setNormalMaximum(1);
                $itemVariables[] = $var;
                
                $var = new \taoResultServer_models_classes_OutcomeVariable();
                $var->setIdentifier('completionStatus');
                $var->setCardinality('single');
                $var->setBaseType('identifier');
                $var->setEpoch(microtime());
                $var->setValue('completed');
                $itemVariables[] = $var;
                
                $var = new \taoResultServer_models_classes_ResponseVariable();
                $var->setIdentifier('RESPONSE');
                $var->setCardinality('single');
                $var->setBaseType('identifier');
                $var->setEpoch(microtime());
                $var->setValue('choice1');
                $var->setCorrectResponse(true);
                $itemVariables[] = $var;
                
                $var = new \taoResultServer_models_classes_ResponseVariable();
                $var->setIdentifier('numAttempts');
                $var->setCardinality('single');
                $var->setBaseType('integer');
                $var->setEpoch(microtime());
                $var->setValue(1);
                $itemVariables[] = $var;
                
                $var = new \taoResultServer_models_classes_ResponseVariable();
                $var->setIdentifier('duration');
                $var->setCardinality('single');
                $var->setBaseType('string');
                $var->setEpoch(microtime());
                $var->setValue('PT10S');
                $itemVariables[] = $var;
                
                $time[] = $this->storeItemVariableSet("deliveryResultIdentifier${i}", "test", "item${j}", $itemVariables, "callIdItem${j}");
            }
        }
        
        $report->add(
            new Report(
                Report::TYPE_INFO,
                "Simulation is composed of ${deliveryExecutionCount} delivery executions. ${attemptCount} candidate attempts are performed for each delivery execution."
            )
        );
        
        $report->add(
            new Report(
                Report::TYPE_INFO,
                'Total time spent in storing candidate attempts related variables: ' . array_sum($time) . ' seconds.'
            )
        );
        
        $report->add(
            new Report(
                Report::TYPE_INFO,
                'Average time spent in storing candidate attempts related variables: ' . (array_sum($time) / ($attemptCount * $deliveryExecutionCount)) . ' seconds.'
            )
        );

        if ($purge) {

            $sqlPersistence = $this->storage->getPersistence();
            $dbPlatform = $sqlPersistence->getPlatform();
            
            if (in_array($dbPlatform->getName(), ['postgresql', 'mysql'])) {
                foreach ([RdsResultStorage::VARIABLES_TABLENAME, RdsResultStorage::RESULTS_TABLENAME] as $tbl) {
                    $dbTruncateSql = $dbPlatform->getTruncateTableSql($tbl);
                    $sqlPersistence->exec($dbTruncateSql . " CASCADE");
                }
                
                $report->add(
                    new Report(
                        Report::TYPE_WARNING,
                        "Your RDS Result Storage has just been purged!"
                    )
                );
            } else {
                $report->add(
                    new Report(
                        Report::TYPE_ERROR,
                        "The purge feature is only available with 'mysql' and 'postgresql' RDBMS platforms."
                    )
                );
            }
        }
        
        return $report;
    }
    
    abstract protected function storeItemVariableSet($deliveryResultIdentifier, $testIdentifier, $itemIdentifier, array $variables, $callIdItem);
}

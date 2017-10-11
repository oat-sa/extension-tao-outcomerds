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
use common_report_Report as Report;

abstract class AbstractStoreItemVariable extends AbstractAction
{
    protected $storage;
    
    public function __invoke($params)
    {        
        $report = new Report(
            Report::TYPE_INFO,
            "The script ended gracefully."
        );
        
        $this->storage = $this->getServiceManager()->get('taoOutcomeRds/RdsResultStorage');
        
        $time = [];
        
        for ($i = 0; $i < intval($params[0]); $i++) {
            
            $this->storage->storeRelatedTestTaker("deliveryResultIdentifier${i}", "testTakerIdentifier${i}");
            $this->storage->storeRelatedDelivery("deliveryResultIdentifier${i}", "deliveryIdentifier");
            
            for ($j = 0; $j < intval($params[1]); $j++) {
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
                $var->setIdentifier('SCORE');
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
                
                $this->storeItemVariableSet("deliveryResultIdentifier${i}", "test", "item${j}", $itemVariables, "callIdItem${j}", $time);
            }
        }
        
        $report->add(
            new Report(
                Report::TYPE_SUCCESS,
                "Total time spent in '" . $this->getBenchmarkMethodName() . "' in seconds: " . array_sum($time)
            )
        );
        
        return new \common_report_Report(
            \common_report_Report::TYPE_INFO,
            array_sum($time)
        );
    }
    
    abstract protected function storeItemVariableSet($deliveryResultIdentifier, $testIdentifier, $itemIdentifier, array $variables, $callIdItem, array &$time);
    
    abstract protected function getBenchmarkMethodName();
}

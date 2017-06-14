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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoOutcomeRds\scripts\update;

use Doctrine\DBAL\Schema\SchemaException;
use oat\taoOutcomeRds\model\RdsResultStorage;

/**
 * Migration class which adds `delivery_execution` column to `results_storage` table
 *
 * Class AlterTables
 * @package oat\taoOutcomeRds\scripts\update
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class AlterTables extends \common_ext_action_InstallAction
{
    protected $persistence;

    public function __invoke($params)
    {
        $this->persistence = \common_persistence_Manager::getPersistence('default');

        /** @var common_persistence_sql_pdo_SchemaManager $schemaManager */
        $schemaManager = $this->persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        try {
            $tableResults = $schema->getTable(RdsResultStorage::RESULTS_TABLENAME);
            $tableResults->addColumn(RdsResultStorage::DELIVERY_EXECUTION_COLUMN, "string", array("notnull" => false, "length" => 255));
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }

        $queries = $this->persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $this->persistence->exec($query);
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Tables successfully altered'));
    }
}

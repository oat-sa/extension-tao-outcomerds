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
 * Copyright (c) 2019 Open Assessment Technologies SA
 */

namespace oat\taoOutcomeRds\model;

use common_persistence_SqlPersistence as Persistence;
use Doctrine\DBAL\Schema\Table;

interface CompatibleSchemaInterface
{
    /**
     * Returns the field to sort item and test variables.
     * @return string
     */
    public function getSortingField();

    /**
     * Returns additional fields to prepare insert statement.
     * @param Persistence $persistence
     * @return array
     */
    public function getAdditionalFieldForInsert(Persistence $persistence);

    /**
     * Returns column definitions for the table "variables".
     * @return array
     */
    public function addColumnsForTableVariables(Table $tableVariables);
}

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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */
declare(strict_types=1);

namespace oat\taoOutcomeRds\model;

use common_persistence_SqlPersistence;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\service\ConfigurableService;
use Doctrine\DBAL\DBALException;

/**
 * Class DummyFeatureManager.
 *
 * This is the implementation of my DummyFeatureManager Service.
 *
 * @package oat\taoOutcomeRds\model
 */
class DummyFeatureManager extends ConfigurableService
{
    /** @var string The DummyFeatureManager Service ID ('extensionName/className'). */
    public const SERVICE_ID = 'taoOutcomeRds/DummyFeatureManager';

    /** @var string It is a good practice to make the persistence configurable. */
    public const OPTION_PERSISTENCE = 'persistence';

    /** @var string The 'dummy_option' key. */
    public const OPTION_DUMMY_OPTION = 'dummy';

    /** @var string The name of the new database table required by the DummyFeatureManager. */
    protected const DUMMY_TABLE_NAME = 'dummytable';

    /** @var common_persistence_SqlPersistence */
    protected $persistence;

    /**r
     * DummyFeatueManager constructor.
     *
     * A ConfigurableService always receive an array of options at
     * instantiation time.
     *
     * @param array $options An array of Service options.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Get Persistence.
     *
     * Retrieve the appropriate persistence depending on the self::OPTION_PERSISTENCE
     * option. If no value is set for option self::OPTION_PERSISTENCE, the 'default'
     * persistence will be retrieve to access the database.
     *
     * @return common_persistence_SqlPersistence
     */
    public function getPersistence(): common_persistence_SqlPersistence
    {
        $persistenceId = $this->hasOption(self::OPTION_PERSISTENCE) ? $this->getOption(self::OPTION_PERSISTENCE) : 'default';
        $this->persistence = $this->getServiceLocator()
                                  ->get(PersistenceManager::SERVICE_ID)
                                  ->getPersistenceById($persistenceId);

        return $this->persistence;
    }

    /**
     * Do Something.
     *
     * Do something in the scope of the DummyFeatureManager Service.
     */
    public function doSomething(): void
    {
        // Let us do some database stuff.
        $persistence = $this->getPersistence();
        $dummyOption = $this->getOption(self::OPTION_DUMMY_OPTION);
        if ($dummyOption === 'dummy') {
            // Do something dummy...
        } else {
            // Do something else...
        }
    }

    /**
     * Upgrade Database.
     *
     * Implementation of the database upgrade consisting of creating
     * a new 'dummytable' with a sinble 'dummycolumn' varchar(255) column.
     *
     * @throws DBALException
     */
    public function upgradeDatabase(): void
    {
        // Get the current schema and clone it.
        $persistence = $this->getPersistence();
        $schema = $persistence->getSchemaManager()->createSchema();
        $fromSchema = clone $schema;

        // Perform changes.
        $table = $schema->createTable(self::DUMMY_TABLE_NAME);
        $table->addColumn('dummycolumn', 'string', ['length' => 255]);

        // Execute schema transformation.
        $persistence->getPlatForm()->migrateSchema($fromSchema, $schema);

        $this->getLogger()->debug('Migration Schema upgrade done.');
    }

    /**
     * Downgrade Database.
     *
     * Implementation of the database downgrade consisting of dropping
     * the 'dummytable'.
     *
     * @throws DBALException
     */
    public function downgradeDatabase(): void
    {
        // Get the current schema and clone it.
        $persistence = $this->getPersistence();
        $schema = $persistence->getSchemaManager()->createSchema();
        $fromSchema = clone $schema;

        $schema->dropTable(self::DUMMY_TABLE_NAME);

        // Execute schema transformation.
        $persistence->getPlatForm()->migrateSchema($fromSchema, $schema);
        $this->getLogger()->debug('Migration Schema downgrade done.');
    }
}

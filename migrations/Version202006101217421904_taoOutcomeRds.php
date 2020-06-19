<?php

declare(strict_types=1);

namespace oat\taoOutcomeRds\migrations;

use common_Exception;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoOutcomeRds\model\DummyFeatureManager;

/**
 * Class Version202006101217421904_taoOutcomeRds.
 *
 * The Migration Class implementing the necessary changes to introduce/remove
 * a Dummy Feature in extension taoOutcomeRds.
 *
 * @package oat\taoOutcomeRds\migrations
 */
final class Version202006101217421904_taoOutcomeRds extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'New Dummy Feature for extension taoOutcomeRds.';
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     * @throws common_Exception
     */
    public function up(Schema $schema): void
    {
        $dummyFeatureManager = new DummyFeatureManager([
            DummyFeatureManager::OPTION_DUMMY_OPTION => 'dummy'
        ]);

        /*
         * This might throw a common_Exception. This is why it is reported
         * in the PhpDoc above. We will let the Migration Manager taking care
         * of it in case of error.
         */
        $this->getServiceLocator()->register(
            DummyFeatureManager::SERVICE_ID,
            $dummyFeatureManager
        );

        // Apply database upgrade.
        $dummyFeatureManager->upgradeDatabase();
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        /** @var DummyFeatureManager $dummyFeatureManager */
        $dummyFeatureManager = $this->getServiceLocator()->get(DummyFeatureManager::SERVICE_ID);
        $dummyFeatureManager->downgradeDatabase();

        // Unregister DummyFeatureManager service.
        $this->getServiceLocator()->unregister(DummyFeatureManager::SERVICE_ID);
    }
}

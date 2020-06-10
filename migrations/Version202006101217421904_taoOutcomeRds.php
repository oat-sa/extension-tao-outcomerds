<?php

declare(strict_types=1);

namespace oat\taoOutcomeRds\migrations;

use common_Exception;
use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoOutcomeRds\model\DummyFeatureManager;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202006101217421904_taoOutcomeRds extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'New Dummy Feature for extension taoOutcomeRds.';
    }

    /**
     * @param Schema $schema
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
        $dummyFeatureManager->upgradeDatabase($schema);

        /*
         * The Migration Manager will now take care of executing the appropriate
         * queries to perform the database upgrade.
         */
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        /** @var DummyFeatureManager $dummyFeatureManager */
        $dummyFeatureManager = $this->getServiceLocator()->get(DummyFeatureManager::SERVICE_ID);
        $dummyFeatureManager->downgradeDatabase($schema);

        // Unregister DummyFeatureManager service.
        $this->getServiceLocator()->unregister(DummyFeatureManager::SERVICE_ID);

        /*
         * The Migration Manager will now take care of executing the appropriate
         * queries to perform the database downgrade.
         */
    }
}

<?php

declare(strict_types=1);

namespace oat\taoOutcomeRds\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use oat\oatbox\reporting\Report;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoOutcomeRds\model\AbstractRdsResultStorage;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202303231411201904_taoOutcomeRds extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Added new database column "externallyGraded" to table "variables_storage"';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable(AbstractRdsResultStorage::VARIABLES_TABLENAME)
            ->addColumn(AbstractRdsResultStorage::IS_EXTERNALLY_GRADED, Types::BOOLEAN, ['default' => false]);

        $this->addReport(Report::createSuccess($this->getDescription()));
    }

    public function down(Schema $schema): void
    {
        $schema->getTable(AbstractRdsResultStorage::VARIABLES_TABLENAME)
            ->dropColumn(AbstractRdsResultStorage::IS_EXTERNALLY_GRADED);

        $this->addReport(Report::createWarning('Column database "externallyGraded" on table "variables_storage" was dropped'));

    }
}

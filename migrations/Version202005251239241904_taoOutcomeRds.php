<?php

declare(strict_types=1);

namespace oat\taoOutcomeRds\migrations;

use Doctrine\DBAL\Schema\Schema;
use Exception;
use oat\tao\scripts\tools\migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202005251239241904_taoOutcomeRds extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A failing test migration for extension taoOutcomeRds (3).';
    }

    public function up(Schema $schema): void
    {
        throw new Exception('Migration error.');

        $this->getLogger()->debug("Log message never appearing. Otherwise something is wrong.");
    }

    public function down(Schema $schema): void
    {
        $this->getLogger()->debug("Rollback never happens, otherwise something is wrong.");
    }
}

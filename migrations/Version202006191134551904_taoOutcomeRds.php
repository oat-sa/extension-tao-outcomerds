<?php

declare(strict_types=1);

namespace oat\taoOutcomeRds\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202006191134551904_taoOutcomeRds extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Sequence Testing';
    }

    public function up(Schema $schema): void
    {
        $this->getLogger()->debug('Version202006191134551904_taoOutcomeRds UP!');
    }

    public function down(Schema $schema): void
    {
        $this->getLogger()->debug('Version202006191134551904_taoOutcomeRds DOWN!');
    }
}

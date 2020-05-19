<?php

declare(strict_types=1);

namespace oat\taoOutcomeRds\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\oatbox\service\ServiceManagerAwareInterface;
use oat\oatbox\service\ServiceManagerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202005191854501904_taoOutcomeRds extends AbstractMigration implements ServiceManagerAwareInterface
{
    use ServiceManagerAwareTrait;

    public function getDescription(): string
    {
        return 'A test migration for extension taoOutcomeRds (2).';
    }

    public function up(Schema $schema): void
    {
        /** @var LoggerService $logger */
        $logger = $this->getServiceLocator()->get(LoggerService::LOGGER_OPTION);
        $logger->debug("taoOutcomeRds Migration 2 UP.");
    }

    public function down(Schema $schema): void
    {
        /** @var LoggerService $logger */
        $logger = $this->getServiceLocator()->get(LoggerService::LOGGER_OPTION);
        $logger->debug("taoOutcomeRds Migration 2 DOWN.");
    }
}
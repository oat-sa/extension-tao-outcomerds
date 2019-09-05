<?php
/**
 * Default config header
 *
 * To replace this add a file /home/bout/code/php/taoTrunk/taoResultServer/config/header/default_resultserver.conf.php
 */
use oat\taoOutcomeRds\model\RdsResultStorage;
use oat\taoOutcomeRds\model\RdsCompatibleSchema;

return new RdsResultStorage([
    RdsResultStorage::OPTION_PERSISTENCE => 'default',
    RdsResultStorage::OPTION_COMPATIBLE_SCHEMA => new RdsCompatibleSchema(),
]);

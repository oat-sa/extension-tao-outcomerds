<?php
/**
 * Default config header
 *
 * To replace this add a file /home/bout/code/php/taoTrunk/taoResultServer/config/header/default_resultserver.conf.php
 */

use oat\taoOutcomeRds\model\NewSqlResultStorage;

return new NewSqlResultStorage([
    NewSqlResultStorage::OPTION_PERSISTENCE => 'default',
]);

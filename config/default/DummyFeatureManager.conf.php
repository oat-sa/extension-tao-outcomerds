<?php

declare(strict_types=1);

use oat\taoOutcomeRds\model\DummyFeatureManager;

/*
 * Instructions to be executed to properly instantiate the DummyFeatureManager
 * Service when it is retrieved for the first time by the TAO ServiceManager.
 */
return new DummyFeatureManager([
    DummyFeatureManager::OPTION_DUMMY_OPTION => 'dummy'
]);

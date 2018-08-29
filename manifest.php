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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

return array(
    'name' => 'taoOutcomeRds',
    'label' => 'extension-tao-outcomerds',
    'description' => 'extension that allows a storage in relational database',
    'license' => 'GPL-2.0',
    'version' => '4.2.4',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'taoResultServer' => '>=6.2.0',
        'generis' => '>=5.1.0'
    ),
    // for compatibility
    'dependencies' => array('tao', 'taoResultServer'),
    'models' => array(
        'http://www.tao.lu/Ontologies/taoOutcomeRds.rdf#'
    ),
    'install' => array(
        'rdf' => array(
            dirname(__FILE__) . '/scripts/install/taoOutcomeRds.rdf'
        ),
        'php' => array(
            \oat\taoOutcomeRds\scripts\install\createTables::class,
            dirname(__FILE__) . '/scripts/install/setDefault.php',
        )
    ),
    'uninstall' => array(
        'php' => array(
            \oat\taoOutcomeRds\scripts\uninstall\removeTables::class
        )
    ),
    'update' => 'oat\\taoOutcomeRds\\scripts\\update\\Updater',
    'autoload' => array(
        'psr-4' => array(
            'oat\\taoOutcomeRds\\' => dirname(__FILE__) . DIRECTORY_SEPARATOR
        )
    ),
    'routes' => array(
        '/taoOutcomeRds' => 'oat\\taoOutcomeRds\\controller'
    ),
    'constants' => array(
        # views directory
        "DIR_VIEWS" => dirname(__FILE__) . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,
        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoOutcomeRds/',
    ),
);

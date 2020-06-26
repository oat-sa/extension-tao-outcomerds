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

return [
    'name' => 'taoOutcomeRds',
    'label' => 'extension-tao-outcomerds',
    'description' => 'extension that allows a storage in relational database',
    'license' => 'GPL-2.0',
    'version' => '7.3.1',
    'author' => 'Open Assessment Technologies SA',
    'requires' => [
        'taoResultServer' => '>=11.0.0',
        'generis' => '>=12.15.0'
    ],
    // for compatibility
    'dependencies' => ['tao', 'taoResultServer'],
    'models' => [
        'http://www.tao.lu/Ontologies/taoOutcomeRds.rdf#'
    ],
    'install' => [
        'rdf' => [
            dirname(__FILE__) . '/scripts/install/taoOutcomeRds.rdf'
        ],
        'php' => [
            \oat\taoOutcomeRds\scripts\install\CreateTables::class,
        ]
    ],
    'uninstall' => [
        'php' => [
            \oat\taoOutcomeRds\scripts\uninstall\removeTables::class
        ]
    ],
    'update' => 'oat\\taoOutcomeRds\\scripts\\update\\Updater',
    'autoload' => [
        'psr-4' => [
            'oat\\taoOutcomeRds\\' => dirname(__FILE__) . DIRECTORY_SEPARATOR
        ]
    ],
    'routes' => [
        '/taoOutcomeRds' => 'oat\\taoOutcomeRds\\controller'
    ],
    'constants' => [
        # views directory
        "DIR_VIEWS" => dirname(__FILE__) . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,
        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoOutcomeRds/',
    ],
];

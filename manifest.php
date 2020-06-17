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

use \oat\taoOutcomeRds\scripts\install\CreateTables;

return [
    'name' => 'taoOutcomeRds',
    'label' => 'extension-tao-outcomerds',
    'description' => 'extension that allows a storage in relational database',
    'license' => 'GPL-2.0',
    'version' => '7.3.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => [
        'taoResultServer' => '>=11.0.0',
        'generis' => '>=12.26.0'
    ],
    'install' => [
        'php' => [
            CreateTables::class,
        ]
    ],
    'uninstall' => [
        'php' => [
            \oat\taoOutcomeRds\scripts\uninstall\removeTables::class
        ]
    ],
    'update' => 'oat\\taoOutcomeRds\\scripts\\update\\Updater',
];

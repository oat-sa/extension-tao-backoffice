<?php

use oat\taoBackOffice\controller\Redirector;
use oat\tao\model\user\TaoRoles;

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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

return [
    'name' => 'taoBackOffice',
    'label' => 'Back Office',
    'description' => 'Base for back-office extensions',
    'license' => 'GPL-2.0',
    'version' => '4.5.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => [
        'tao' => '>=22.5.0',
        'generis' => '>=5.10.0'
    ],
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoBackOfficeManager',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoBackOfficeManager', ['ext' => 'taoBackOffice']],
        ['grant', 'http://www.tao.lu/Ontologies/TAO.rdf#PropertyManagerRole', ['controller' => 'oat\taoBackOffice\controller\Lists']],
        ['grant', TaoRoles::BACK_OFFICE, Redirector::class . '@redirectTaskToInstance'],
    ],
    'install' => [
        'rdf' => [
            __DIR__ . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . 'ontology' . DIRECTORY_SEPARATOR . 'structures.rdf'
        ],
        'php' => [
            __DIR__ . '/scripts/install/registerEntryPoint.php'
        ]
    ],
    'uninstall' => [
    ],
    'routes' => [
        '/taoBackOffice' => 'oat\\taoBackOffice\\controller'
    ],
    'update' => 'oat\taoBackOffice\scripts\update\Updater',
    'constants' => [
        # views directory
        "DIR_VIEWS" => __DIR__ . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoBackOffice/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ]
];

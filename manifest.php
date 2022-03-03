<?php

use oat\tao\model\user\TaoRoles;
use oat\taoBackOffice\controller\Lists;
use oat\taoBackOffice\controller\Redirector;
use oat\tao\model\accessControl\func\AccessRule;
use oat\taoBackOffice\model\lists\ListServiceProvider;
use oat\taoBackOffice\model\ListElement\ListElementServiceProvider;

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
 * Copyright (c) 2015-2021 (original work) Open Assessment Technologies SA;
 */

return [
    'name' => 'taoBackOffice',
    'label' => 'Back Office',
    'description' => 'Base for back-office extensions',
    'license' => 'GPL-2.0',
    'author' => 'Open Assessment Technologies SA',
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoBackOfficeManager',
    'acl' => [
        [
            AccessRule::GRANT,
            'http://www.tao.lu/Ontologies/generis.rdf#taoBackOfficeManager',
            ['ext' => 'taoBackOffice'],
        ],
        [
            AccessRule::GRANT,
            TaoRoles::PROPERTY_MANAGER,
            ['act' => Lists::class .'@getListElements'],
        ],
        [
            AccessRule::GRANT,
            TaoRoles::BACK_OFFICE,
            Redirector::class . '@redirectTaskToInstance',
        ],
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
        'DIR_VIEWS' => __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoBackOffice/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ],
    'containerServiceProviders' => [
        ListServiceProvider::class,
        ListElementServiceProvider::class,
    ]
];

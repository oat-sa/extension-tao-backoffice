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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoBackOffice\migrations;

use common_report_Report as Report;
use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\user\TaoRoles;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoBackOffice\controller\Lists;
use oat\taoItems\model\user\TaoItemsRoles;

final class Version202203021737164734_taoBackOffice extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update global UI config';
    }

    public function up(Schema $schema): void
    {
        $resultConfig = [];
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoBackOffice');
        $existingConfig = $ext->getConfig('globalUIConfig');
        if($existingConfig !== false) {
            $resultConfig = $existingConfig;
        }

        $config = [
            'tao' => [
                'client_lib_config_registry' => [
                    'item/mediaAlignment' => [
                        'ui/image/ImgStateActive' => ['mediaAlignment'],
                    ],
                    'test/BRS' => [
                        'taoQtiTest/controller/creator/views/item' => ['BRS'],
                    ],
                    'item/question/hasMath' => [
                        'taoQtiItem/qtiCreator/widgets/interactions/extendedTextInteraction/states/Question' => ['hasMath'],
                    ],
                    'item/question/disallowHTMLInHottext' => [
                        'taoQtiItem/qtiCreator/widgets/interactions/hottextInteraction/states/Question'=> ['disallowHTMLInHottext'],
                    ]
                ]
            ]
        ];

        foreach($config as $extensionName => $extensionConfig) {
            $currentExt = \common_ext_ExtensionsManager::singleton()->getExtensionById($extensionName);

            foreach($extensionConfig as $configFileName => $existingConfig) {
                $currentExtConfig = $currentExt->getConfig($configFileName);

                foreach($existingConfig as $globalConfigKey => $configData) {
                    foreach($configData as $configKey => $singleConfig) {
                        if(!array_key_exists($configKey, $currentExtConfig)) {
                            continue;
                        }
                        foreach($singleConfig as $value) {
                            if(!array_key_exists($value, $currentExtConfig[$configKey])) {
                                continue;
                            }
                            $param = $currentExtConfig[$configKey][$value];
                            $resultConfig[$globalConfigKey] = $param ? 'show' : 'hide';
                        }
                    }
                }
            }
        }

        $ext->setConfig('globalUIConfig', $resultConfig);
    }

    public function down(Schema $schema): void
    {

    }

}

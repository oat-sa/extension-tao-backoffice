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

final class Version202203021737164743_taoBackOffice extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update global UI config';
    }

    const SHOW_OPTION = 'show';
    const HIDE_OPTION = 'hide';
    const GLOBAL_UI_CONFIG_NAME = 'globalUIConfig';

    const DEFAULT_CONFIG = [
        'item/image/mediaAlignment' => 'hide',
        'test/item/BRS' => 'hide',
        'item/multiColumn' => 'hide',
        'item/scrollableMultiColumn' => 'hide',
        'item/perInteractionRp' => 'hide',
        'item/response/modalFeedbacks' => 'hide',
        'item/commonInteractions/choiceInteraction/shufflingChoices' => 'hide',
        'item/commonInteractions/orderInteraction/shufflingChoices' => 'hide',
        'item/commonInteractions/extendedTextInteraction/hasMath' => 'hide',
        'item/commonInteractions/hottextInteraction/disallowHTMLInHottext' => 'hide',
        'item/customInteractions/customInteractions' => 'hide',
        'item/customInteractions/likertScaleInteraction' => 'hide',
        'item/customInteractions/liquidsInteraction' => 'hide',
        'test/properties/item/itemSessionControl/showFeedback' => 'hide',
        'test/properties/item/itemSessionControl/allowComment' => 'hide',
        'test/properties/item/itemSessionControl/allowSkipping' => 'hide',
        'test/properties/item/timeLimits' => 'hide',
        'test/properties/section/itemSessionControl/showFeedback' => 'hide',
        'test/properties/section/itemSessionControl/allowComment' => 'hide',
        'test/properties/section/itemSessionControl/allowSkipping' => 'hide',
        'test/properties/section/timeLimits' => 'hide',
        'test/properties/testPart/itemSessionControl' => 'hide',
        'test/properties/testPart/timeLimits' => 'hide',
        'test/properties/test/timeLimits' => 'hide',
        'delivery/taoLocal' => 'hide'
    ];

    const EXISTING_CONFIG_MAP = [
        'tao' => [
            'client_lib_config_registry' => [
                'item/image/mediaAlignment' => [
                    'ui/image/ImgStateActive' => ['mediaAlignment'],
                ],
                'test/item/BRS' => [
                    'taoQtiTest/controller/creator/views/item' => ['BRS'],
                ],
                'item/commonInteractions/extendedTextInteraction/hasMath' => [
                    'taoQtiItem/qtiCreator/widgets/interactions/extendedTextInteraction/states/Question' => ['hasMath'],
                ],
                'item/commonInteractions/hottextInteraction/disallowHTMLInHottext' => [
                    'taoQtiItem/qtiCreator/widgets/interactions/hottextInteraction/states/Question'=> ['disallowHTMLInHottext'],
                ]
            ]
        ],
        'taoQtiItem' => [
            'qtiCreator' => [
                'item/multiColumn' => [
                    'multi-column' => []
                ],
                'item/scrollableMultiColumn' => [
                    'scrollable-multi-column' => []
                ],
                'item/perInteractionRp' => [
                    'perInteractionRp' => []
                ],
            ]
        ]
    ];

    public function up(Schema $schema): void
    {
        $resultConfig = self::DEFAULT_CONFIG;
        $backOfficeExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoBackOffice');
        if($backOfficeExtension === null) {
            throw new \Exception(sprintf('Cannot find %s extension', 'taoBackOffice'));
        }
        $existingConfig = $backOfficeExtension->getConfig(self::GLOBAL_UI_CONFIG_NAME);
        if($existingConfig !== false) {
            $resultConfig = $existingConfig;
        }

        foreach(self::EXISTING_CONFIG_MAP as $extensionName => $extensionConfig) {
            $currentExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById($extensionName);
            if($currentExtension === null) {
                throw new \Exception(sprintf('Cannot find %s extension', $extensionName));
            }
            foreach($extensionConfig as $configFileName => $existingConfig) {
                $currentExtensionConfig = $currentExtension->getConfig($configFileName);
                if($currentExtensionConfig === false) {
                    throw new \Exception(sprintf('Cannot find %s config', $configFileName));
                }

                $this->updateGlobalUIConfig($existingConfig, $currentExtensionConfig,$resultConfig);
            }
        }

        $backOfficeExtension->setConfig(self::GLOBAL_UI_CONFIG_NAME, $resultConfig);
    }

    private function updateGlobalUIConfig($existingConfig, $currentExtConfig, array &$resultConfig): void
    {
        foreach($existingConfig as $globalConfigKey => $configData) {
            foreach($configData as $configKey => $singleConfig) {
                if(!array_key_exists($configKey, $currentExtConfig)) {
                    continue;
                }
                if (!empty($singleConfig)) {
                    foreach($singleConfig as $value) {
                        if(!array_key_exists($value, $currentExtConfig[$configKey])) {
                            continue;
                        }
                        $resultConfig[$globalConfigKey] = $currentExtConfig[$configKey][$value] ? self::SHOW_OPTION : self::HIDE_OPTION;
                    }
                } else {
                    $resultConfig[$globalConfigKey] = $currentExtConfig[$configKey] ? self::SHOW_OPTION : self::HIDE_OPTION;
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Global Ui config update could not be reverted'
        );
    }
}

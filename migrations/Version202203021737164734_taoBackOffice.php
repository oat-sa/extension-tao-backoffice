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

final class Version202203021737164754_taoBackOffice extends AbstractMigration
{
    private const SHOW_OPTION = 'show';
    private const HIDE_OPTION = 'hide';
    private const GLOBAL_UI_CONFIG_NAME = 'globalUIConfig';

    public function getDescription(): string
    {
        return 'Update global UI config';
    }

    public function up(Schema $schema): void
    {
        $resultConfig = require "./taoBackOffice/config/default/globalUIConfig.conf.php";
        $backOfficeExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoBackOffice');
        if ($backOfficeExtension === null) {
            throw new \Exception(sprintf('Cannot find %s extension', 'taoBackOffice'));
        }
        $existingConfig = $backOfficeExtension->getConfig(self::GLOBAL_UI_CONFIG_NAME);
        if ($existingConfig !== false) {
            $resultConfig = $existingConfig;
        }

        $resultConfig = array_merge($resultConfig, $this->getFromTaoCore());
        $resultConfig = array_merge($resultConfig, $this->getFromItemQti());

        $backOfficeExtension->setConfig(self::GLOBAL_UI_CONFIG_NAME, $resultConfig);
    }

    private function getFromTaoCore(): array
    {
        $config = $this->loadConfig('tao', 'client_lib_config_registry');

        return [
            'item/image/mediaAlignment' => $this->visibilityAsString($config['ui/image/ImgStateActive']['mediaAlignment'] ?? false),
            'test/item/BRS' => $this->visibilityAsString($config['taoQtiTest/controller/creator/views/item']['BRS'] ?? false),
            'item/commonInteractions/extendedTextInteraction/hasMath' => $this->visibilityAsString($config['taoQtiItem/qtiCreator/widgets/interactions/extendedTextInteraction/states/Question']['hasMath'] ?? false),
            'item/commonInteractions/hottextInteraction/disallowHTMLInHottext' => $this->visibilityAsString($config['taoQtiItem/qtiCreator/widgets/interactions/hottextInteraction/states/Question']['disallowHTMLInHottext'] ?? false),
        ];
    }

    private function getFromItemQti(): array
    {
        $config = $this->loadConfig('taoQtiItem', 'qtiCreator');

        return [
            'item/multiColumn' => $this->visibilityAsString($config['multi-column'] ?? false),
            'item/scrollableMultiColumn' => $this->visibilityAsString($config['scrollable-multi-column'] ?? false),
            'item/perInteractionRp' => $this->visibilityAsString($config['perInteractionRp'] ?? false),
        ];
    }

    private function loadConfig(string $extensionName, string $configName): array
    {
        $currentExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById($extensionName);

        if ($currentExtension === null) {
            throw new \Exception(sprintf("Extension %s cannot be found", 'tao'));
        }

        $config = $currentExtension->getConfig($configName);
        if ($config === false) {
            throw new \Exception(sprintf("Config %s cannot be read", 'tao'));
        }

        return $config;
    }

    private function visibilityAsString(bool $isVisible): string
    {
        return $isVisible ? self::SHOW_OPTION : self::HIDE_OPTION;
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Global Ui config update could not be reverted'
        );
    }
}

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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoBackOffice\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\menu\SectionVisibilityFilter;

class MapPasswordControlFeatureFlag extends InstallAction
{
    public function __invoke($params)
    {
        $sectionVisibilityFilter = $this->getServiceManager()->get(SectionVisibilityFilter::SERVICE_ID);
        $featureFlagSections = $sectionVisibilityFilter
            ->getOption(SectionVisibilityFilter::OPTION_FEATURE_FLAG_SECTIONS_TO_HIDE);

        $featureFlagSections['settings_my_password'] = [
            FeatureFlagCheckerInterface::FEATURE_FLAG_PASSWORD_CHANGE_DISABLED
        ];

        $sectionVisibilityFilter->setOption(
            SectionVisibilityFilter::OPTION_FEATURE_FLAG_SECTIONS_TO_HIDE,
            $featureFlagSections
        );

        $this->getServiceManager()->register(SectionVisibilityFilter::SERVICE_ID, $sectionVisibilityFilter);
    }
}

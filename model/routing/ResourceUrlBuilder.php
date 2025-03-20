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
 * Copyright (c) 2018-2022 (original work) Open Assessment Technologies SA.
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoBackOffice\model\routing;

use LogicException;
use oat\tao\model\menu\Tree;
use InvalidArgumentException;
use core_kernel_classes_Class;
use oat\tao\model\menu\Section;
use core_kernel_classes_Resource;
use oat\tao\model\menu\MenuService;
use oat\tao\model\menu\Perspective;
use oat\oatbox\service\ConfigurableService;

class ResourceUrlBuilder extends ConfigurableService
{
    public const SERVICE_ID = 'taoBackOffice/resourceUrlBuilder';

    /**
     * Builds a full URL for a resource
     *
     * @return string
     */
    public function buildUrl(core_kernel_classes_Resource $resource)
    {
        $resourceClass = $resource->getClass($resource);

        if (!$resource->exists() && !$resourceClass->exists()) {
            throw new InvalidArgumentException('The requested resource does not exist or has been deleted');
        }

        if (!$resource->isClass()) {
            $resourceClass = array_values($resource->getTypes())[0];
        }

        /** @var Perspective $perspective */
        foreach (MenuService::getAllPerspectives() as $perspective) {
            /** @var Section $section */
            foreach ($perspective->getChildren() as $section) {
                if ($this->isSectionApplicable($resourceClass, $section)) {
                    return $this->getBackofficeUrl($perspective, $section, $resource);
                }
            }
        }

        throw new LogicException(
            sprintf(
                'No url could be built for "%s"',
                $resource->getUri()
            )
        );
    }

    /**
     * Generates the actual URL based on perspective and section
     *
     * @return string
     */
    private function getBackofficeUrl(
        Perspective $perspective,
        Section $section,
        core_kernel_classes_Resource $resource
    ) {
        return _url('index', 'Main', 'tao', [
            'structure' => $perspective->getId(),
            'ext' => $perspective->getExtension(),
            'section' => $section->getId(),
            'uri' => $resource->getUri(),
        ]);
    }

    private function isSectionApplicable(core_kernel_classes_Class $resourceClass, Section $section): bool
    {
        /** @var Tree $tree */
        foreach ($section->getTrees() as $tree) {
            $rootClass = $resourceClass->getClass($tree->get('rootNode'));

            if ($rootClass->equals($resourceClass) || $resourceClass->isSubClassOf($rootClass)) {
                return true;
            }
        }

        return false;
    }
}

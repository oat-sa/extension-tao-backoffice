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
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;

/**
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class ResourceUrlBuilder extends ConfigurableService
{
    use OntologyAwareTrait;

    public const SERVICE_ID = 'taoBackOffice/resourceUrlBuilder';

    /**
     * Builds a full URL for a resource
     *
     * @return string
     */
    public function buildUrl(core_kernel_classes_Resource $resource)
    {
        $resourceClass = $this->getClass($resource);

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

        throw new LogicException('No url could be built for "' . $resource->getUri() . '"');
    }

    /**
     * Generates the actual URL based on perspective and section
     */
    private function getBackofficeUrl(
        Perspective $perspective,
        Section $section,
        core_kernel_classes_Resource $resource
    ) {
        return _url('index', 'Main', 'tao', [
            'structure' => $perspective->getId(),
            'section' => $section->getId(),
            'uri' => $resource->getUri(),
        ]);
    }

    /**
     * @return bool
     */
    private function isSectionApplicable(core_kernel_classes_Class $resourceClass, Section $section)
    {
        /** @var Tree $tree */
        foreach ($section->getTrees() as $tree) {
            $rootClass = $this->getClass($tree->get('rootNode'));

            if ($rootClass->equals($resourceClass) || $resourceClass->isSubClassOf($rootClass)) {
                return true;
            }
        }

        return false;
    }
}

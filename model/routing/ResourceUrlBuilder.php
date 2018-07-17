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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoBackOffice\model\routing;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\menu\MenuService;
use oat\tao\model\menu\Perspective;
use oat\tao\model\menu\Section;
use oat\tao\model\menu\Tree;

/**
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class ResourceUrlBuilder extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoBackOffice/resourceUrlBuilder';

    /**
     * Builds a full URL for a resource
     *
     * @param \core_kernel_classes_Resource $resource
     * @return string
     */
    public function buildUrl(\core_kernel_classes_Resource $resource)
    {
        /** @var Perspective $perspective */
        /** @var Section $section */

        foreach (MenuService::getAllPerspectives() as $perspective) {
            foreach ($perspective->getChildren() as $section) {
                if ($this->isSectionApplicable($resource, $section)) {
                    $url = _url('index', 'Main', 'tao', [
                        'structure' => $perspective->getId(),
                        'section' => $section->getId(),
                        'uri' => $resource->getUri(),
                    ]);

                    return $url;
                }
            }
        }

        throw new \LogicException('No url could be built for "'. $resource->getUri() .'"');
    }

    /**
     * @param \core_kernel_classes_Resource $resource
     * @param Section                       $section
     * @return bool
     */
    private function isSectionApplicable(\core_kernel_classes_Resource $resource, Section $section)
    {
        $resourceClass = $resource->isClass()
            ? $this->getClass($resource)
            : array_values($resource->getTypes())[0];

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
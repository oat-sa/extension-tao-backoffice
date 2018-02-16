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

class ResourceUrlBuilder extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoBackOffice/resourceUrlBuilder';

    const OPTION_CLASS_EXTENSION_ASSOCIATIONS = 'class_to_extension_associations';

    /**
     * Builds a full URL to be used by front-end for a resource based on the OPTION_CLASS_EXTENSION_ASSOCIATIONS map
     *
     * Add your association if it is missing.
     *
     * @param \core_kernel_classes_Resource $resource
     * @return string
     */
    public function buildUrl(\core_kernel_classes_Resource $resource)
    {
        if ($resource->isClass()) {
            $resourceClass = $this->getClass($resource);
        } else {
            $classes = $resource->getTypes();
            $resourceClass = array_shift($classes);
            unset($classes);
        }

        foreach ((array) $this->getOption(self::OPTION_CLASS_EXTENSION_ASSOCIATIONS) as $classUri => $extensionId) {
            if ($resourceClass->isSubClassOf($this->getClass($classUri))) {
                $perspectives = array_filter(MenuService::getAllPerspectives(), function (Perspective $perspective) use ($extensionId){
                    return $perspective->getExtension() == $extensionId;
                });

                /** @var Perspective $perspective */
                $perspective = array_shift($perspectives);

                unset($perspectives);

                $children = $perspective->getChildren();

                /** @var Section $section */
                $section = array_shift($children);

                unset($children);

                return _url('index', 'Main', 'tao', [
                    'structure' => $perspective->getId(),
                    'ext' => $perspective->getExtension(),
                    'section' => $section->getId(),
                    'uri' => $resource->getUri()
                ]);
            }
        }

        throw new \LogicException('No url could be built for "'. $resource->getUri() .'"');
    }

    /**
     * Adds a new association between a class uri and an extension.
     *
     * @param string|\core_kernel_classes_Class $classUri
     * @param string $extensionId
     */
    public function addAssociation($classUri, $extensionId)
    {
        $classObj = $this->getClassObject($classUri);

        $associations = (array) $this->getOption(self::OPTION_CLASS_EXTENSION_ASSOCIATIONS);

        $associations[ (string) $classObj->getUri() ] = (string) $extensionId;

        $this->setOption(self::OPTION_CLASS_EXTENSION_ASSOCIATIONS, $associations);
    }

    /**
     * Gets the extension id set for a given class uri
     *
     * @param string|\core_kernel_classes_Class $classUri
     * @return string
     */
    public function getExtensionForClass($classUri)
    {
        $classObj = $this->getClassObject($classUri);

        $associations = (array) $this->getOption(self::OPTION_CLASS_EXTENSION_ASSOCIATIONS);

        if (array_key_exists($classObj->getUri(), $associations)) {
            return $associations[$classObj->getUri()];
        }

        throw new \LogicException('No extension associated with "'. $classObj->getUri() .'"');
    }

    /**
     * @param $classUri
     * @return \core_kernel_classes_Class
     */
    private function getClassObject($classUri)
    {
        if (is_string($classUri)) {
            $classObj = $this->getClass($classUri);
        } else if ($classUri instanceof \core_kernel_classes_Class) {
            $classObj = $classUri;
        } else {
            throw new \InvalidArgumentException('Either a core_kernel_classes_Class instance or a class uri is accepted.');
        }

        if (!$classObj->exists()) {
            throw new \InvalidArgumentException('Class "' . $classObj->getUri() . '" does not exist.');
        }

        return $classObj;
    }
}
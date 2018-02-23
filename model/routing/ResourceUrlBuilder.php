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
     * Add your url config if it is missing.
     *
     * @param \core_kernel_classes_Resource $resource
     * @throws \LogicException
     * @return string
     */
    public function buildUrl(\core_kernel_classes_Resource $resource)
    {
        if ($resource->isClass()) {
            $resourceClass = $this->getClass($resource);
        } else {
            $resourceClass = array_values($resource->getTypes())[0];
        }

        foreach ((array) $this->getOption(self::OPTION_CLASS_EXTENSION_ASSOCIATIONS) as $classUri => $urlConf) {
            if ($resourceClass->isSubClassOf($this->getClass($classUri))) {
                return _url('index', 'Main', 'tao', array_merge($urlConf, [
                    'uri' => $resource->getUri()
                ]));
            }
        }

        throw new \LogicException('No url could be built for "'. $resource->getUri() .'"');
    }

    /**
     * Adds a new url configuration for a class uri.
     *
     * @param string|\core_kernel_classes_Class $classUri
     * @param string $extensionId
     */
    public function addUrlConfig($classUri, $extensionId)
    {
        $classObj = $this->getClassObject($classUri);

        $associations = (array) $this->getOption(self::OPTION_CLASS_EXTENSION_ASSOCIATIONS);

        /** @var Perspective $perspective */
        $perspective = array_values(array_filter(MenuService::getAllPerspectives(), function (Perspective $perspective) use ($extensionId){
            return $perspective->getExtension() == $extensionId;
        }))[0];

        /** @var Section $section */
        $section = $perspective->getChildren()[0];

        $associations[ (string) $classObj->getUri() ] = [
            'ext' => (string) $extensionId,
            'structure' => $perspective->getId(),
            'section' => $section->getId()
        ];

        $this->setOption(self::OPTION_CLASS_EXTENSION_ASSOCIATIONS, $associations);
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
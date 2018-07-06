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

    const OPTION_CACHE_SERVICE = 'cache';

    /**
     * @var \common_cache_Cache
     */
    private $cache;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (empty($this->getOption(self::OPTION_CACHE_SERVICE))) {
            throw new \InvalidArgumentException("Cache Service needs to be set for ". __CLASS__);
        }
    }

    /**
     * Builds a full URL for a resource
     *
     * @param \core_kernel_classes_Resource $resource
     * @return string
     * @throws \common_cache_NotFoundException
     */
    public function buildUrl(\core_kernel_classes_Resource $resource)
    {
        $cacheKey = md5('buildUrl'. $resource->getUri());

        if ($this->getCache()->has($cacheKey)) {
            return $this->getCache()->get($cacheKey);
        }

        $resourceClass = $resource->isClass()
            ? $this->getClass($resource)
            : array_values($resource->getTypes())[0];

        /** @var Perspective $perspective */
        /** @var Section $section */
        /** @var Tree $tree */

        foreach (MenuService::getAllPerspectives() as $perspective) {
            foreach ($perspective->getChildren() as $section) {
                foreach ($section->getTrees() as $tree) {
                    $rootClass = $this->getClass($tree->get('rootNode'));
                    if ($rootClass->equals($resourceClass) || $resourceClass->isSubClassOf($rootClass)) {
                        $url = _url('index', 'Main', 'tao', [
                            'structure' => $perspective->getId(),
                            'section' => $section->getId(),
                            'uri' => $resource->getUri(),
                        ]);

                        $this->getCache()->put($url, $cacheKey);

                        return $url;
                    }
                }
            }
        }

        throw new \LogicException('No url could be built for "'. $resource->getUri() .'"');
    }

    /**
     * @return \common_cache_Cache
     */
    protected function getCache()
    {
        if (is_null($this->cache)) {
            $this->cache = $this->getServiceLocator()->get($this->getOption(self::OPTION_CACHE_SERVICE));
        }

        return $this->cache;
    }
}
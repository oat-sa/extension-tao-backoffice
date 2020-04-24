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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 *
 */

declare(strict_types = 1);

namespace oat\taoBackOffice\model\menuStructure;

use common_ext_ExtensionsManager as ExtensionsManager;
use oat\tao\model\accessControl\ActionResolver;
use oat\tao\model\accessControl\func\AclProxy as FuncProxy;

/**
 * Class MenuService
 * @package oat\taoBackOffice\model\menuStructure
 */
class MenuService extends \oat\tao\model\menu\MenuService
{

    /**
     * identifier to use to cache the structures
     * @var string
     */
    const CACHE_KEY = 'tao_structures';

    /**
     * Use caching mechanism for the structure.
     * For performances issues, cache should be enabled.
     * @var boolean
     */
    const USE_CACHE = true;

    /**
     * to stock the extension structure
     *
     * @access protected
     * @var array
     */
    protected $structure = [];

    /**
     * Load the extension structure file.
     * Return the SimpleXmlElement object (don't forget to cast it)
     * @param $extensionId
     * @return mixed|string|null
     * @throws \common_ext_ExtensionException
     * @throws \common_ext_ManifestNotFoundException
     */
    protected function getStructuresFilePath($extensionId)
    {
        $extension = $this->getExtensionsManager()->getExtensionById($extensionId);
        $extra = $extension->getManifest()->getExtra();
        if (isset($extra['structures'])) {
            $structureFilePath = $extra['structures'];
        } else {
            $structureFilePath = $extension->getDir() . 'actions/structures.xml';
        }

        if (file_exists($structureFilePath)) {
            return $structureFilePath;
        } else {
            return null;
        }
    }

    /**
     * Get the structure content (from the structure.xml file) of each extension.
     * @return array
     * @throws \common_exception_Error
     */
    public function getAllPerspectives()
    {
        $structure = $this->readStructure();
        return $structure['perspectives'];
    }

    /**
     * Reads the structure data.
     * This method manages to cache the structure if needed.
     * @return array
     * @throws \common_exception_Error
     */
    private function readStructure()
    {
        if (count($this->structure) == 0) {
            if (self::USE_CACHE == false) {
                //rebuild structure each time
                $this->structure = $this->buildStructures();
            } else {
                //cache management
                try {
                    $this->structure = $this->getServiceLocator()->get('generis/cache')->get(self::CACHE_KEY);
                } catch (\common_cache_NotFoundException $e) {
                    $this->structure = $this->buildStructures();
                    $this->getServiceLocator()->get('generis/cache')->put($this->structure, self::CACHE_KEY);
                }
            }
        }
        return $this->structure;
    }

    /**
     * Get the structure content (from the structure.xml file) of each extension.
     * @return array
     * @throws \common_exception_Error
     * @throws \common_ext_ExtensionException
     * @throws \common_ext_ManifestNotFoundException]
     */
    private function buildStructures()
    {
        $perspectives = [];
        $toAdd = [];
        $sorted = \helpers_ExtensionHelper::sortByDependencies($this->getExtensionsManager()->getEnabledExtensions());
        foreach (array_keys($sorted) as $extId) {
            $file = self::getStructuresFilePath($extId);
            if (!is_null($file)) {
                $xmlStructures = new \SimpleXMLElement($file, 0, true);
                $extStructures = $xmlStructures->xpath("/structures/structure");
                foreach ($extStructures as $xmlStructure) {
                    $perspective = Perspective::fromSimpleXMLElement($xmlStructure, $extId);
                    if (!isset($perspectives[$perspective->getId()])) {
                        $perspectives[$perspective->getId()] = $perspective;
                    } else {
                        foreach ($perspective->getChildren() as $section) {
                            $perspectives[$perspective->getId()]->addSection($section);
                        }
                    }
                }
                foreach ($xmlStructures->xpath("/structures/toolbar/toolbaraction") as $xmlStructure) {
                    $perspective = Perspective::fromLegacyToolbarAction($xmlStructure, $extId);
                    $perspectives[$perspective->getId()] = $perspective;
                    if (isset($xmlStructure['structure'])) {
                        $toAdd[$perspective->getId()] = (string)$xmlStructure['structure'];
                    }
                }
            }
        }

        foreach ($toAdd as $to => $from) {
            if (isset($perspectives[$from]) && isset($perspectives[$to])) {
                foreach ($perspectives[$from]->getChildren() as $section) {
                    $perspectives[$to]->addSection($section);
                }
            }
        }

        usort($perspectives, function ($a, $b) {
            return $a->getLevel() - $b->getLevel();
        });

        return [
            'perspectives' => $perspectives
        ];
    }

    /**
     * @return ExtensionsManager
     */
    private function getExtensionsManager()
    {
        return $this->getServiceLocator()->get(ExtensionsManager::SERVICE_ID);
    }

    public function flushCache()
    {
        $this->structure = [];
        $this->getServiceLocator()->get('generis/cache')->remove(self::CACHE_KEY);
    }

    /**
     * Get perspective data depending on the group set in structure.xml
     *
     * @param $groupId
     * @return array
     */
    public function getNavigationElementsByGroup($groupId)
    {
        $entries = [];
        foreach ($this->getPerspectivesByGroup($groupId) as $i => $perspective) {
            $binding = $perspective->getBinding();
            $children = $this->getMenuElementChildren($perspective);

            if (!empty($binding) || !empty($children)) {
                $entry = [
                    'perspective' => $perspective,
                    'children'    => $children
                ];
                if (!is_null($binding)) {
                    $entry['binding'] = $perspective->getExtension() . '/' . $binding;
                }
                $entries[$i] = $entry;
            }
        }
        return $entries;
    }

    /**
     * Get the sections of the current extension's structure
     *
     * @param string $shownExtension
     * @param string $shownStructure
     * @param $user
     * @return array the sections
     */
    public function getSections($shownExtension, $shownStructure, $user)
    {
        $sections = [];
        $structure = $this->getPerspective($shownExtension, $shownStructure);
        if ($structure === null) {
            return $sections;
        }

        foreach ($structure->getChildren() as $section) {
            $resolver = new ActionResolver($section->getUrl());
            if (FuncProxy::accessPossible($user, $resolver->getController(), $resolver->getAction())) {
                foreach ($section->getActions() as $action) {
                    $this->propagate($action);
                    $resolver = new ActionResolver($action->getUrl());
                    if (!FuncProxy::accessPossible($user, $resolver->getController(), $resolver->getAction())) {
                        $section->removeAction($action);
                    }
                }
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * Get nested menu elements depending on user rights.
     *
     * @param Perspective $menuElement from the structure.xml
     * @return array menu elements list
     */
    private function getMenuElementChildren(Perspective $menuElement)
    {
        $user = \common_session_SessionManager::getSession()->getUser();
        $children = [];
        foreach ($menuElement->getChildren() as $section) {
            try {
                $resolver = new ActionResolver($section->getUrl());
                if (FuncProxy::accessPossible($user, $resolver->getController(), $resolver->getAction())) {
                    $children[] = $section;
                }
            } catch (\ResolverException $e) {
                $this->logWarning('Invalid reference in structures: ' . $e->getMessage());
            }
        }
        return $children;
    }
}

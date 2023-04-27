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
 * Copyright (c) 2014-2023 (original work) Open Assessment Technologies SA.
 */

namespace oat\taoBackOffice\model\menuStructure;

use core_kernel_classes_Class;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\AbstractRegistry;
use oat\tao\model\menu\MenuService;
use oat\tao\model\TaoOntology;
use tao_models_classes_GenerisService;

/**
 * Class TreeService
 */
class ClassActionRegistry extends AbstractRegistry
{
    public const CLASS_PREFIX = 'class_';

    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\AbstractRegistry::getExtension()
     */
    protected function getExtension()
    {
        return \common_ext_ExtensionsManager::singleton()->getExtensionById('taoBackOffice');
    }

    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\AbstractRegistry::getConfigId()
     */
    protected function getConfigId()
    {
        return 'classActionRegistry';
    }

    /**
     * Returns all the actions associated with this class and its parents
     *
     * @param core_kernel_classes_Class $class
     * @return array an array of Action
     */
    public function getClassActions(core_kernel_classes_Class $class)
    {
        $actions = [];
        foreach ($this->getRelevantClasses($class) as $rClass) {
            if ($this->isRegistered($rClass->getUri())) {
                $actions = array_merge($actions, $this->get($rClass->getUri()));
            }
        }
        return $actions;
    }

    /**
     * Register an action with a class
     *
     * @param core_kernel_classes_Class $class
     * @param Action $action
     */
    public function registerAction(core_kernel_classes_Class $class, Action $action)
    {
        $actions = $this->isRegistered($class->getUri())
            ? $this->get($class->getUri())
            : [];
        $actions[$action->getId()] = $action;
        $this->set($class->getUri(), $actions);
        MenuService::flushCache();
    }

    public function unregisterAction(core_kernel_classes_Class $class, Action $action)
    {
        $actions = $this->isRegistered($class->getUri())
            ? $this->get($class->getUri())
            : [];
        unset($actions[$action->getId()]);
        $this->set($class->getUri(), $actions);
        MenuService::flushCache();
    }

    private function getRelevantClasses(core_kernel_classes_Class $class)
    {
        $toDo = [$class->getUri() => $class];
        $classes = [];
        while (!empty($toDo)) {
            $current = array_pop($toDo);
            $classes[$current->getUri()] = $current;
            if (
                !in_array(
                    $current->getUri(),
                    [TaoOntology::OBJECT_CLASS_URI, GenerisRdf::CLASS_GENERIS_RESOURCE, OntologyRdfs::RDFS_CLASS ]
                )
            ) {
                foreach ($current->getParentClasses(false) as $parent) {
                    if (!in_array($parent->getUri(), array_keys($classes))) {
                        $toDo[$parent->getUri()] = $parent;
                    }
                }
            }
        }
        return $classes;
    }
}

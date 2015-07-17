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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoBackOffice\model\tree;

use core_kernel_classes_Class;
use core_kernel_classes_Property;

/**
 * Class TreeService
 */
class TreeService {

    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAO.rdf#Tree';

    const PROPERTY_ROOT_NODE = 'http://www.tao.lu/Ontologies/TAO.rdf#TreeRootNode';

    const PROPERTY_PARENT_NODE = 'http://www.tao.lu/Ontologies/TAO.rdf#TreeParent';

    /**
     * @param core_kernel_classes_Class $tree
     *
     * @return array
     */
    public function getTreeStructure(core_kernel_classes_Class $tree)
    {
        return $this->buildTreeStructure($tree, $this->getRootNode($tree));
    }

    protected function buildTreeStructure(core_kernel_classes_Class $tree, \core_kernel_classes_Resource $node)
    {
        $returnValue = array(
        	'id' => $node->getUri(),
            'label' => $node->getLabel(),
            'children' => array()
        );

        $children = $tree->searchInstances(array(
        	self::PROPERTY_PARENT_NODE => $node
        ), array('like' => false));

        foreach ($children as $child) {
            $returnValue['children'][] = $this->buildTreeStructure($tree, $child);
        }
        return $returnValue;
    }

    public function getFlatStructure(core_kernel_classes_Class $tree)
    {
        return $this->buildFlatStructure($tree, $this->getRootNode($tree));
    }

    public function buildFlatStructure(core_kernel_classes_Class $tree, \core_kernel_classes_Resource $node){

        $returnValue = array(
            'nodes' => array( array( 'id' => $node->getUri(), 'label' => $node->getLabel() ) ),
            'edges' => array(),
        );

        $children = $tree->searchInstances(
            array(
                self::PROPERTY_PARENT_NODE => $node
            ),
            array( 'like' => false )
        );

        foreach ($children as $child) {
            $returnValue['edges'][] = array( 'from' => $node->getUri(), 'to' => $child->getUri() );
            $returnValue            = array_merge_recursive( $returnValue, $this->buildFlatStructure( $tree, $child ) );
        }

        return $returnValue;
    }


    /**
     * @param core_kernel_classes_Class $tree
     *
     * @return \core_kernel_classes_Container
     * @throws \core_kernel_classes_EmptyProperty
     * @throws \core_kernel_classes_MultiplePropertyValuesException
     */
    public function getRootNode(core_kernel_classes_Class $tree)
    {
        return $tree->getUniquePropertyValue(new core_kernel_classes_Property(self::PROPERTY_ROOT_NODE));
    }

}
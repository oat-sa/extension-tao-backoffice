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

use tao_models_classes_ClassService;
use core_kernel_classes_Class;
use core_kernel_classes_Property;

/**
 * Class TreeService
 */
class TreeService extends tao_models_classes_ClassService {

    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAO.rdf#Tree';

    const PROPERTY_CHILD_OF = 'http://www.tao.lu/Ontologies/TAO.rdf#isChildOf';
    
    public function getRootClass()
    {
        return new core_kernel_classes_Class(self::CLASS_URI);
    }

    public function getFlatStructure(core_kernel_classes_Class $tree)
    {
        $returnValue = array(
            'nodes' => array(),
            'edges' => array()
        );
        
        $childOf = new \core_kernel_classes_Property(self::PROPERTY_CHILD_OF);
        foreach ($tree->getInstances() as $node) {
            $returnValue['nodes'][] = array(
                'id' => $node->getUri(),
                'label' => $node->getLabel()
            );
            foreach ($node->getPropertyValues($childOf) as $childUri) {
                $returnValue['edges'][] = array(
                    'from' => $childUri,
                    'to' => $node->getUri()
                );
            }
        }
        return $returnValue;
    }

}
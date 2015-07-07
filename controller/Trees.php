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
 * Copyright (c) 2002-2008 (original work) Public Research Centre Henri Tudor & University of Luxembourg (under the project TAO & TAO2);
 *               2008-2010 (update and modification) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *               2013 (update and modification) Open Assessment Technologies SA;
 * 
 */

namespace oat\taoBackOffice\controller;

use tao_helpers_Scriptloader;
use tao_models_classes_ListService;
use tao_actions_form_List;
use tao_helpers_Uri;
use core_kernel_classes_Class;
use oat\taoBackOffice\model\tree\TreeService;

/**
 * This controller provide the actions to manage the lists of data
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package taoBackOffice
 * 
 *
 */
class Trees extends \tao_actions_CommonModule {

	/**
	 * Constructor performs initializations actions
	 * @return void
	 */
	public function __construct(){

		parent::__construct();

		//$this->defaultData();
	}
	
	/**
	 * 
	 * @return \core_kernel_classes_Class
	 */
	protected function getRootClass()
	{
	    return new core_kernel_classes_Class(TreeService::CLASS_URI);
	}

	/**
	 * Visualises the tree
	 */
	public function viewTree()
	{
	    $tree = new core_kernel_classes_Class($this->getRequestParameter('uri'));
	    $treeService = new TreeService();
	    $struct = $treeService->getTreeStructure($tree);
	    
	    // debug code
	    echo '<pre>';
	    var_dump($struct);
	    echo '</pre>';
	     
	}
	
	public function getTreeData()
	{
	    $data = array(
	        'data' => __("Trees"),
	        'attributes' => array(
	            'id' => $this->getRootClass()->getUri(),
	            'class' => 'node-class'
	        ),
	        'children' => array()
	    );
	    foreach ($this->getRootClass()->getSubClasses(false) as $class) {
	        $data['children'][] = array(
	            'data' => $class->getLabel(),
	            'attributes' => array(
	                'id' => $class->getUri(),
	                'class' => 'node-instance'
	            )
	        );
	    }
	
	    $this->returnJson($data);
	}
	
}

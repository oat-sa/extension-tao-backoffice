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
 * Copyright (c) 2008-2010 (original work) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *               2021 (update and modification) Open Assessment Technologies SA;
 */

namespace oat\taoBackOffice\model\lists;

use tao_helpers_form_FormContainer;
use tao_helpers_form_FormFactory;

/**
 * This container is used to initialize the "Create List" form.
 *
 * @access public
 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
 * @package taoBackOffice
 */
class CreateListForm extends tao_helpers_form_FormContainer
{
    /**
     * Builds a new form used to create a new list
     *
     * @access public
     * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
     * @return mixed
     */
    public function initForm()
    {
        $this->form = tao_helpers_form_FormFactory::getForm('createList');

        $addElt = tao_helpers_form_FormFactory::getElement('add', 'Free');
        
        $addElt->setValue(
            sprintf(
                '<a href="#" class="form-submitter btn-success"><span class="icon-add"></span> %s</a>',
                __('Create list')
            )
        );

        $this->form->setActions([$addElt]);
    }

    /**
     * {@inheritDoc}
     */
    public function initElements()
    {
        // void
    }
}

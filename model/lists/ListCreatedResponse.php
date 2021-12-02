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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoBackOffice\model\lists;

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use JsonSerializable;
use tao_helpers_Uri;

class ListCreatedResponse implements JsonSerializable
{
    /** @var core_kernel_classes_Class */
    private $list;

    /** @var core_kernel_classes_Resource[] */
    private $elements;

    public function __construct(core_kernel_classes_Class $list, array $elements = [])
    {
        $this->list     = $list;
        $this->elements = $elements;
    }

    public function jsonSerialize(): array
    {
        $elementsView = [];

        foreach ($this->elements as $element) {
            $elementsView[] = [
                'uri' => tao_helpers_Uri::encode($element->getUri()),
                'label' => $element->getLabel()
            ];
        }

        return [
            'uri'      => tao_helpers_Uri::encode($this->list->getUri()),
            'label'    => $this->list->getLabel(),
            'elements' => $elementsView
        ];
    }
}

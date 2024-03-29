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

declare(strict_types=1);

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

    /** @var int */
    private $totalCount;

    public function __construct(core_kernel_classes_Class $list, array $elements = [], int $totalCount = 0)
    {
        $this->list = $list;
        $this->elements = $elements;
        $this->totalCount = $totalCount;
    }

    public function jsonSerialize(): array
    {
        $elementsView = [];

        foreach ($this->elements as $element) {
            $elementsView[] = [
                'uri' => $element->getUri(),
                'label' => $element->getLabel()
            ];
        }

        return [
            'uri' => $this->list->getUri(),
            'label' => $this->list->getLabel(),
            'elements' => $elementsView,
            'totalCount' => $this->totalCount,
        ];
    }
}

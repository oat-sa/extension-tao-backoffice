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

use Iterator;
use oat\tao\model\Specification\ClassSpecificationInterface;

class ListCreator
{
    /** @var ListService */
    private $listService;

    /** @var ClassSpecificationInterface */
    private $remoteListClassSpecification;

    public function __construct(
        ListService $listService,
        ClassSpecificationInterface $remoteListClassSpecification
    ) {
        $this->listService = $listService;
        $this->remoteListClassSpecification = $remoteListClassSpecification;
    }

    public function createEmptyList(): ListCreatedResponse
    {
        $newName = __('List') . ' ' . ($this->getListCount() + 1);

        $list = $this->listService->createList($newName);
        $this->listService->createListElement($list, __('Element') . ' 1');

        $elements = $this->listService->getListElements($list);

        if ($elements instanceof Iterator) {
            $elements = iterator_to_array($elements);
        }

        return new ListCreatedResponse($list, $elements);
    }

    private function getListCount(): int
    {
        $accumulator = 0;

        // @todo Count from database instead of loading all lists in memory
        foreach ($this->listService->getLists() as $listClass) {
            if (!$this->remoteListClassSpecification->isSatisfiedBy($listClass)) {
                ++$accumulator;
            }
        }

        return $accumulator;
    }
}

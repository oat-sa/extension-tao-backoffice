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

namespace oat\taoBackOffice\model\ListElement\Service;

use core_kernel_classes_Class;
use oat\tao\model\Context\ContextInterface;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\taoBackOffice\model\ListElement\Context\ListElementsFinderContext;
use oat\taoBackOffice\model\ListElement\Contract\ListElementsFinderInterface;

class ListElementsFinder implements ListElementsFinderInterface
{
    /** @var ValueCollectionService */
    private $valueCollectionService;

    public function __construct(ValueCollectionService $valueCollectionService)
    {
        $this->valueCollectionService = $valueCollectionService;
    }

    public function find(ContextInterface $context): ValueCollection
    {
        $request = new ValueCollectionSearchRequest();

        /** @var core_kernel_classes_Class $listClass */
        $listClass = $context->getParameter(ListElementsFinderContext::PARAMETER_LIST_CLASS);
        $request->setValueCollectionUri($listClass->getUri());

        $totalCount = $this->valueCollectionService->count(new ValueCollectionSearchInput($request));

        $offset = $context->getParameter(ListElementsFinderContext::PARAMETER_OFFSET, 0);

        if ($offset) {
            $request->setOffset($offset);
        }

        $limit = $context->getParameter(ListElementsFinderContext::PARAMETER_LIMIT, 0);

        if ($limit) {
            $request->setLimit($limit);
        }

        $valueCollection = $this->valueCollectionService->findAll(new ValueCollectionSearchInput($request));
        $valueCollection->setTotalCount($totalCount);

        return $valueCollection;
    }
}

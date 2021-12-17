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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoBackOffice\model\ListElement\Service;

use core_kernel_classes_Class;
use oat\tao\model\Context\ContextInterface;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\taoBackOffice\model\ListElement\Context\ListElementsFinderContext;
use oat\taoBackOffice\model\ListElement\Contract\ListElementsFinderInterface;

class ListElementsFinder implements ListElementsFinderInterface
{
    /** @var ClassSpecificationInterface */
    private $remoteListClassSpecification;

    /** @var ValueCollectionService */
    private $valueCollectionService;

    /** @var int */
    private $localListElementsLimit;

    /** @var int */
    private $remoteListElementsLimit;

    public function __construct(
        ClassSpecificationInterface $remoteListClassSpecification,
        ValueCollectionService $valueCollectionService,
        int $localListElementsLimit,
        int $remoteListElementsLimit
    ) {
        $this->remoteListClassSpecification = $remoteListClassSpecification;
        $this->valueCollectionService = $valueCollectionService;
        $this->localListElementsLimit = $localListElementsLimit;
        $this->remoteListElementsLimit = $remoteListElementsLimit;
    }

    public function find(ContextInterface $context): ValueCollection
    {
        /** @var core_kernel_classes_Class $listClass */
        $listClass = $context->getParameter(ListElementsFinderContext::PARAMETER_LIST_CLASS);

        $request = new ValueCollectionSearchRequest();
        $request->setValueCollectionUri($listClass->getUri());

        $totalCount = $this->valueCollectionService->count(new ValueCollectionSearchInput($request));

        $this
            ->setRequestOffset($request, $context)
            ->setRequestLimit($request, $context);

        $valueCollection = $this->valueCollectionService->findAll(new ValueCollectionSearchInput($request));
        $valueCollection->setTotalCount($totalCount);

        return $valueCollection;
    }

    private function setRequestOffset(ValueCollectionSearchRequest $request, ContextInterface $context): self
    {
        $offset = $context->getParameter(ListElementsFinderContext::PARAMETER_OFFSET, 0);

        if ($offset) {
            $request->setOffset($offset);
        }

        return $this;
    }

    private function setRequestLimit(ValueCollectionSearchRequest $request, ContextInterface $context): void
    {
        $limit = $this->getLimit($context);

        if ($limit) {
            $request->setLimit($limit);
        }
    }

    private function getLimit(ContextInterface $context): int
    {
        if ($context->getParameter(ListElementsFinderContext::PARAMETER_LIMIT) === null) {
            /** @var core_kernel_classes_Class $listClass */
            $listClass = $context->getParameter(ListElementsFinderContext::PARAMETER_LIST_CLASS);
            $isRemoteList = $this->remoteListClassSpecification->isSatisfiedBy($listClass);

            return $isRemoteList
                ? $this->remoteListElementsLimit
                : $this->localListElementsLimit;
        }

        return $context->getParameter(ListElementsFinderContext::PARAMETER_LIMIT);
    }
}

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
use oat\tao\model\Lists\Business\Domain\Value;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\taoBackOffice\model\ListElement\Context\ListElementsFinderContext;
use oat\taoBackOffice\model\ListElement\Contract\ListElementsFinderInterface;

class ListElementsFinderProxy implements ListElementsFinderInterface
{
    /** @var ClassSpecificationInterface */
    private $remoteListClassSpecification;

    /** @var ListElementsFinderInterface */
    private $listElementsFinder;

    /** @var int */
    private $localListElementsLimit;

    /** @var int */
    private $remoteListElementsLimit;

    public function __construct(
        ClassSpecificationInterface $remoteListClassSpecification,
        ListElementsFinderInterface $listElementsFinder,
        int $localListElementsLimit,
        int $remoteListElementsLimit
    ) {
        $this->remoteListClassSpecification = $remoteListClassSpecification;
        $this->listElementsFinder = $listElementsFinder;
        $this->localListElementsLimit = $localListElementsLimit;
        $this->remoteListElementsLimit = $remoteListElementsLimit;
    }

    public function find(ContextInterface $context): ValueCollection
    {
        /** @var core_kernel_classes_Class $listClass */
        $listClass = $context->getParameter(ListElementsFinderContext::PARAMETER_LIST_CLASS);
        $isRemoteList = $this->remoteListClassSpecification->isSatisfiedBy($listClass);

        $this->setLimit($context, $isRemoteList);

        $listElements = $this->listElementsFinder->find($context);
        $this->postProcessRemoteListElements($listElements, $isRemoteList);

        return $listElements;
    }

    private function setLimit(ContextInterface $context, bool $isRemoteList): void
    {
        if ($isRemoteList || $context->getParameter(ListElementsFinderContext::PARAMETER_LIMIT) === null) {
            $context->setParameter(
                ListElementsFinderContext::PARAMETER_LIMIT,
                $isRemoteList
                    ? $this->remoteListElementsLimit
                    : $this->localListElementsLimit
            );
        }
    }

    private function postProcessRemoteListElements(ValueCollection $listElements, bool $isRemoteList): void
    {
        if ($isRemoteList && $listElements->getTotalCount() > $this->remoteListElementsLimit) {
            $listElements->addValue(new Value(null, '', '...'));
        }
    }
}

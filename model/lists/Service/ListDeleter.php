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

namespace oat\taoBackOffice\model\lists\Service;

use Throwable;
use core_kernel_classes_Class;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\taoBackOffice\model\lists\Contract\ListDeleterInterface;
use oat\generis\model\resource\Context\ResourceRepositoryContext;
use oat\taoBackOffice\model\lists\Exception\ListDeletionException;
use oat\generis\model\resource\Contract\ResourceRepositoryInterface;
use oat\taoBackOffice\model\ListElement\Contract\ListElementsDeleterInterface;
use oat\tao\model\Lists\DataAccess\Repository\ParentPropertyListCachedRepository;

class ListDeleter implements ListDeleterInterface
{
    /** @var ListElementsDeleterInterface */
    private $listElementsDeleter;

    /** @var ClassSpecificationInterface */
    private $remoteListClassSpecification;

    /** @var ParentPropertyListCachedRepository */
    private $parentPropertyListCachedRepository;

    /** @var ResourceRepositoryInterface */
    private $classRepository;

    public function __construct(
        ListElementsDeleterInterface $listElementsDeleter,
        ClassSpecificationInterface $remoteListClassSpecification,
        ParentPropertyListCachedRepository $parentPropertyListCachedRepository,
        ResourceRepositoryInterface $classRepository
    ) {
        $this->listElementsDeleter = $listElementsDeleter;
        $this->remoteListClassSpecification = $remoteListClassSpecification;
        $this->parentPropertyListCachedRepository = $parentPropertyListCachedRepository;
        $this->classRepository = $classRepository;
    }

    public function delete(core_kernel_classes_Class $list): void
    {
        $this->listElementsDeleter->delete($list);

        if ($this->remoteListClassSpecification->isSatisfiedBy($list)) {
            $this->parentPropertyListCachedRepository->deleteCache(
                [
                    ParentPropertyListCachedRepository::OPTION_LIST_URI => $list->getUri(),
                ]
            );
        }

        try {
            $this->classRepository->delete(
                new ResourceRepositoryContext(
                    [
                        ResourceRepositoryContext::PARAM_CLASS => $list,
                    ]
                )
            );
        } catch (Throwable $exception) {
            throw new ListDeletionException(
                sprintf(
                    'Unable to delete list "%s::%s" (%s).',
                    $list->getLabel(),
                    $list->getUri(),
                    $exception->getMessage()
                ),
                __('Unable to delete the selected list')
            );
        }
    }
}

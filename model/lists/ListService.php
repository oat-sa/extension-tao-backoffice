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
 * Copyright (c) 2018-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoBackOffice\model\lists;

use Throwable;
use tao_models_classes_ListService;
use Psr\Container\ContainerInterface;
use core_kernel_classes_Class as RdfClass;
use oat\generis\model\kernel\uri\UriProvider;
use oat\tao\model\Lists\Business\Domain\Value;
use oat\taoBackOffice\model\lists\Service\ListDeleter;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\taoBackOffice\model\lists\Contract\ListDeleterInterface;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\tao\model\Lists\Business\Specification\RemoteListClassSpecification;
use oat\tao\model\Lists\Business\Specification\EditableListClassSpecification;
use oat\tao\model\Lists\DataAccess\Repository\ParentPropertyListCachedRepository;

class ListService extends tao_models_classes_ListService
{
    /** @var int */
    private $maxAllowedListElementsLimit = 1000;

    /**
     * Whenever or not a list is editable
     * The Language list should not be editable.
     *
     * @return bool
     */
    public function isEditable(RdfClass $listClass)
    {
        return $this->getEditableListClassSpecification()->isSatisfiedBy($listClass);
    }

    public function getListElement(RdfClass $listClass, $uri)
    {
        $request = new ValueCollectionSearchRequest();
        $request->setValueCollectionUri($listClass->getUri())->setUris($uri);

        $result = $this->getValueService()->findAll(
            new ValueCollectionSearchInput($request)
        );

        return $result->count() === 0
            ? null
            : iterator_to_array($result->getIterator())[0];
    }

    public function getListElements(RdfClass $listClass, $sort = true, $limit = 0)
    {
        $request = new ValueCollectionSearchRequest();
        $request->setValueCollectionUri($listClass->getUri());

        if ($limit) {
            $request->setLimit($limit);
        }

        $result = $this->getValueService()->findAll(
            new ValueCollectionSearchInput($request)
        );

        return $result->getIterator();
    }

    public function getMaxAllowedListElementsLimit(): int
    {
        return $this->maxAllowedListElementsLimit;
    }

    public function setMaxAllowedListElementsLimit(int $value): self
    {
        $this->maxAllowedListElementsLimit = $value;
        return $this;
    }

    /**
     * @deprecated Use \oat\taoBackOffice\model\lists\Service\ListDeleter::delete()
     *
     * @return bool
     */
    public function removeList(RdfClass $listClass)
    {
        try {
            $this->getListDeleter()->delete($listClass);

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    /**
     * @param string $label
     *
     * @return void
     */
    public function createListElement(RdfClass $listClass, $label = '')
    {
        $valueCollection = new ValueCollection(
            $listClass->getUri(),
            new Value(null, $this->createUri(), $label)
        );

        $this->getValueService()->persist($valueCollection);
    }

    public function isRemote(RdfClass $listClass): bool
    {
        return $this->getRemoteListClassSpecification()->isSatisfiedBy($listClass);
    }

    private function createUri(): string
    {
        return $this->getServiceLocator()->get(UriProvider::class)->provide();
    }

    private function getValueService(): ValueCollectionService
    {
        return $this->getServiceLocator()->get(ValueCollectionService::class);
    }

    private function getParentPropertyListCachedRepository(): ParentPropertyListCachedRepository
    {
        return $this->getServiceLocator()->get(ParentPropertyListCachedRepository::class);
    }

    private function getRemoteListClassSpecification(): ClassSpecificationInterface
    {
        return $this->getContainer()->get(RemoteListClassSpecification::class);
    }

    private function getEditableListClassSpecification(): ClassSpecificationInterface
    {
        return $this->getContainer()->get(EditableListClassSpecification::class);
    }

    private function getListDeleter(): ListDeleterInterface
    {
        return $this->getContainer()->get(ListDeleter::class);
    }

    private function getContainer(): ContainerInterface
    {
        return $this->getServiceLocator()->getContainer();
    }
}

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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoBackOffice\model\lists\Service;

use oat\tao\model\Lists\Business\Domain\Value;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\tao\model\Lists\DataAccess\Repository\ValueConflictException;
use oat\taoBackOffice\model\lists\Contract\ListUpdaterInterface;
use oat\taoBackOffice\model\lists\ListService;
use Psr\Http\Message\ServerRequestInterface;
use core_kernel_classes_Class;
use tao_helpers_Uri;
use BadFunctionCallException;
use OverflowException;
use RuntimeException;

class ListUpdater implements ListUpdaterInterface
{
    /** @var ValueCollectionService */
    private $valueCollectionService;

    /** @var ListService */
    private $listService;

    public function __construct(
        ValueCollectionService $valueCollectionService,
        ListService $listService
    ) {
        $this->valueCollectionService = $valueCollectionService;
        $this->listService = $listService;
    }

    /**
     * @throws BadFunctionCallException if the payload contains too many items
     * @throws OverflowException if the list exceeds the allowed number of items
     * @throws ValueConflictException if element URIs are non-unique
     * @throws RuntimeException if there is an unexpected persistence error
     */
    public function updateByRequest(ServerRequestInterface $request): void
    {
        $post = (array) $request->getParsedBody();

        if (!isset($post['uri'])) {
            throw new BadFunctionCallException('Payload is missing the list URI');
        }

        $listClass = $this->listService->getList(
            tao_helpers_Uri::decode($post['uri'])
        );

        if ($listClass === null) {
            throw new BadFunctionCallException('Provided list class does not exist');
        }

        $payload = $post;

        if (isset($payload['label'])) {
            $listClass->setLabel($payload['label']);
        }

        unset($payload['uri'], $payload['label']);

        if (count($payload) > 500) {
            throw new BadFunctionCallException('Payload contains too many items');
        }

        if (!$this->setListElements($listClass, $payload)) {
            throw new RuntimeException('Error saving list items');
        }
    }

    /**
     * Updates the ValueCollection instance corresponding to a class.
     *
     * The payload is used to call ValueCollectionService::persist(): Depending
     * on the particular underlying repository class used (instance of
     * ValueCollectionRepositoryInterface), that may cause removing all existing
     * items first (i.e. for remote lists) or merging the values provided with
     * pre-existing values.
     */
    private function setListElements(
        core_kernel_classes_Class $listClass,
        array $payload
    ): bool {
        // This method does not really fetch elements (the object returned by
        // getListSearchInput() has a limit set to 0) and assumes the repository
        // (for example, RdfValueCollectionRepository) will check if it needs to
        // update an existing item or insert a new one based on its URI (or
        // absence of).
        //
        // Note also we cannot POST two items with the same former URI, so there
        // is no need to check for duplicates in the input data.
        //
        $collection = $this->valueCollectionService->findAll(
            $this->getListSearchInput($listClass)
        );

        foreach ($this->getElementsFromPayload($payload) as $key => $value) {
            $newUri = trim($payload["uri_$key"] ?? '');
            $this->addElementToCollection($collection, $key, $value, $newUri);
        }

        return $this->valueCollectionService->persist($collection);
    }

    private function addElementToCollection(
        ValueCollection $valueCollection,
        string $key,
        string $value,
        string $newUri
    ): void {
        $element = $this->getValueByUriKey($valueCollection, $key);

        if ($element === null) {
            $valueCollection->addValue(new Value(null, $newUri, $value));
            return;
        }

        $element->setLabel($value);

        if ($newUri) {
            $element->setUri($newUri);
        }
    }

    private function getValueByUriKey(
        ValueCollection $valueCollection,
        string $key
    ): ?Value {
        $encodedUri = preg_replace('/^list-element_[0-9]+_/', '', $key);
        $uri = tao_helpers_Uri::decode($encodedUri);

        if (empty($uri)) {
            return null;
        }

        return $valueCollection->extractValueByUri($uri);
    }

    private function getElementsFromPayload(array $payload): array
    {
        return array_filter(
            $payload,
            function (string $key): bool {
                return (bool)preg_match('/^list-element_/', $key);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    private function getListSearchInput(
        core_kernel_classes_Class $listClass
    ): ValueCollectionSearchInput {
        return new ValueCollectionSearchInput(
            (new ValueCollectionSearchRequest())
                ->setValueCollectionUri($listClass->getUri())
                ->setLimit(0)
        );
    }
}

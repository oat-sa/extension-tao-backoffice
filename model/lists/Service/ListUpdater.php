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

class ListUpdater implements ListUpdaterInterface
{
    /** @var ValueCollectionService */
    private $valueCollectionService;

    /** @var ListService */
    private $listService;

    public function __construct(
        ValueCollectionService $valueCollectionService,
        ListService $listService
    )
    {
        $this->valueCollectionService = $valueCollectionService;
        $this->listService = $listService;
    }

    /**
     * @throws BadFunctionCallException if the payload contains too many items
     * @throws OverflowException if the list exceeds the allowed number of items
     * @throws ValueConflictException if element URIs are non-unique
     */
    public function updateByRequest(ServerRequestInterface $request): bool
    {
        $post = (array) $request->getParsedBody();

        if (!isset($post['uri'])) {
            return false;
        }

        $listClass = $this->listService->getList(
            tao_helpers_Uri::decode($post['uri'])
        );

        if ($listClass === null) {
            return false;
        }

        $payload = $post;

        if (isset($payload['label'])) {
            $listClass->setLabel($payload['label']);
        }

        unset($payload['uri'], $payload['label']);

        if (count($payload) > 500) {
            throw new BadFunctionCallException(
                __('Payload contains too many items')
            );
        }

        return $this->setListElements($listClass, $payload);
    }

    private function setListElements(
        core_kernel_classes_Class $listClass,
        array $payload
    ): bool {
        $clause = $this->getListSearchInput($listClass);
        $elementsFromPayload = $this->getElementsFromPayload($payload);
        $collection = $this->valueCollectionService->findAll($clause);

        foreach ($elementsFromPayload as $key => $value) {
            $newUri = trim($payload["uri_$key"] ?? '');
            $this->addElementToCollection($collection, $key, $value, $newUri);
        }

        return $this->valueCollectionService->persist($collection);
    }

    private function addElementToCollection(
        ValueCollection $valueCollection,
        $key,
        $value,
        $newUri
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

    private function getValueByUriKey(ValueCollection $valueCollection, $key): ?Value
    {
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
            (new ValueCollectionSearchRequest())->setValueCollectionUri(
                $listClass->getUri()
            )
        );
    }
}

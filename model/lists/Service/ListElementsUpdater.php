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

namespace oat\taoBackOffice\model\lists\Service;

use oat\tao\model\Lists\Business\Domain\Value;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\taoBackOffice\model\lists\Contract\ListElementsUpdaterInterface;
use core_kernel_classes_Class;
use tao_helpers_Uri;

class ListElementsUpdater implements ListElementsUpdaterInterface
{
    private const HUGE_LIST_MIN_ITEMS = 1000;
    private const HUGE_LIST_MAX_MEMORY_MB = 500;

    /** @var ValueCollectionService */
    private $valueCollectionService;

    public function __construct(ValueCollectionService $valueCollectionService)
    {
        $this->valueCollectionService = $valueCollectionService;
    }

    public function setListElements(
        core_kernel_classes_Class $listClass,
        array $payload
    ): bool {
        $clause = $this->getListSearchInput($listClass);
        $elementsFromPayload = $this->getElementsFromPayload($payload);

        if ($this->isHugeList($clause, $elementsFromPayload)) {
            $this->raiseMemoryLimit();
        }

        return $this->valueCollectionService->persist(
            $this->mergeCollectionElementsWithPayload(
                $this->valueCollectionService->findAll($clause),
                $elementsFromPayload
            )
        );
    }

    private function mergeCollectionElementsWithPayload(
        ValueCollection $elements,
        array $elementsFromPayload
    ): ValueCollection {
        foreach ($elementsFromPayload as $key => $value) {
            $newUriValue = trim($payload["uri_$key"] ?? '');
            $element = $this->getValueByUriKey($elements, $key);

            if ($element === null || empty($uri)) {
                $elements->addValue(new Value(null, $newUriValue, $value));

                continue;
            }

            $element->setLabel($value);

            if ($newUriValue) {
                $element->setUri($newUriValue);
            }
        }

        return $elements;
    }

    private function getValueByUriKey(ValueCollection $elements, $key): ?Value
    {
        $encodedUri = preg_replace('/^list-element_[0-9]+_/', '', $key);
        $uri = tao_helpers_Uri::decode($encodedUri);

        if (empty($uri)) {
            return null;
        }

        return $elements->extractValueByUri($uri);
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

    private function isHugeList(
        ValueCollectionSearchInput $clause,
        array $listElements
    ): bool {
        $itemsToProcess = max(
            $this->valueCollectionService->count($clause),
            count($listElements)
        );

        return $itemsToProcess > self::HUGE_LIST_MIN_ITEMS;
    }

    private function raiseMemoryLimit(): void
    {
        $currentLimit = $this->getMemoryLimit();
        $maxAllowedBytes = self::HUGE_LIST_MAX_MEMORY_MB * 1024 * 1024;

        if ($currentLimit < $maxAllowedBytes) {
            ini_set('memory_limit', (string)$maxAllowedBytes);
        }
    }

    private function getMemoryLimit(): int
    {
        $rawMemLimit = ini_get('memory_limit');
        if (trim($rawMemLimit) == '') {
            return 0;
        }

        return self::asBytes($rawMemLimit);
    }

    private static function asBytes(string $val): int
    {
        $val = substr(trim($val), 0, -1);

        switch (strtolower($val[strlen($val) - 1])) {
            case 'g':
                $val *= 1024;
                // intentional fallthrough
            case 'm':
                $val *= 1024;
                // intentional fallthrough
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}

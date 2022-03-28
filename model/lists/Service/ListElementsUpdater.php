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
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\tao\model\Lists\DataAccess\Repository\ValueConflictException;
use oat\taoBackOffice\model\lists\Contract\ListElementsUpdaterInterface;
use core_kernel_classes_Class;
use tao_helpers_Uri;
use OverflowException;


/**
 * Application service to update elements for a list
 */
class ListElementsUpdater implements ListElementsUpdaterInterface
{
    private const HUGE_LIST_MIN_ITEMS = 1000;
    private const HUGE_LIST_MAX_MEMORY_MB = 500;

    /**
     * @throws ValueConflictException
     * @throws OverflowException
     */
    public function setListElements(
        ValueCollectionService $valueCollectionService,
        core_kernel_classes_Class $listClass,
        array $payload
    ): bool {
        $clause = $this->getListSearchInput($listClass);
        $listElements = $this->getElementsFromPayload($payload);

        if ($this->isHugeList($valueCollectionService, $clause, $listElements)) {
            $this->raiseMemoryLimit();
        }

        $elements = $valueCollectionService->findAll($clause);

        foreach ($listElements as $key => $value) {
            $encodedUri = preg_replace('/^list-element_[0-9]+_/', '', $key);
            $uri = tao_helpers_Uri::decode($encodedUri);
            $newUriValue = trim($payload["uri_$key"] ?? '');
            $element = $elements->extractValueByUri($uri);

            if ($element === null || empty($uri)) {
                $elements->addValue(new Value(null, $newUriValue, $value));

                continue;
            }

            $element->setLabel($value);

            if ($newUriValue) {
                $element->setUri($newUriValue);
            }
        }

        return $valueCollectionService->persist($elements);
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

    private function getListSearchInput(core_kernel_classes_Class $listClass): ValueCollectionSearchInput
    {
        return new ValueCollectionSearchInput(
            (new ValueCollectionSearchRequest())->setValueCollectionUri(
                $listClass->getUri()
            )
        );
    }

    private function isHugeList(
        ValueCollectionService $valueCollectionService,
        ValueCollectionSearchInput $clause,
        array $listElements
    ): bool {
        $itemsToProcess = max(
            $valueCollectionService->count($clause),
            count($listElements)
        );

        return $itemsToProcess > self::HUGE_LIST_MIN_ITEMS;
    }

    private function raiseMemoryLimit(): void {
        $currentLimit = $this->getMemoryLimit();
        $maxAllowedBytes = self::HUGE_LIST_MAX_MEMORY_MB * 1024 * 1024;

        if ($currentLimit < $maxAllowedBytes) {
            ini_set('memory_limit', (string)$maxAllowedBytes);
        }
    }

    // @todo May be extracted / moved to (another?) service
    private function getMemoryLimit(): int
    {
        $rawMemLimit = ini_get('memory_limit');
        if(trim($rawMemLimit) == '') {
            return 0;
        }

        return self::asBytes($rawMemLimit);
    }

    private static function asBytes(string $val): int
    {
        $val = substr(trim($val), 0, -1);

        switch(strtolower($val[strlen($val)-1])) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}

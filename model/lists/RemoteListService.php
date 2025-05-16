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
 * Copyright (c) 2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoBackOffice\model\lists;

use core_kernel_classes_Class;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\Lists\Business\Domain\RemoteSourceContext;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Lists\Business\Service\RemoteSource;
use oat\tao\model\Lists\Business\Service\RemoteSourcedListOntology;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use RuntimeException;

class RemoteListService
{
    private ValueCollectionService $valueCollectionService;
    private RemoteSource $remoteSource;
    private FeatureFlagChecker $featureFlagChecker;

    public function __construct(
        ValueCollectionService $valueCollectionService,
        RemoteSource $remoteSource,
        FeatureFlagChecker $featureFlagChecker
    ) {
        $this->valueCollectionService = $valueCollectionService;
        $this->remoteSource = $remoteSource;
        $this->featureFlagChecker = $featureFlagChecker;
    }

    public function sync(core_kernel_classes_Class $collectionClass, bool $isReloading = false): void
    {
        $context = $this->createRemoteSourceContext($collectionClass);
        $collection = new ValueCollection(
            $collectionClass->getUri(),
            ...iterator_to_array($this->remoteSource->fetchByContext($context))
        );

        $result = $this->valueCollectionService->persist($collection);

        if (!$result) {
            throw new RuntimeException(
                sprintf(
                    'Attempt for %s of remote list was not successful',
                    $isReloading ? 'reloading' : 'loading'
                )
            );
        }
    }

    private function createRemoteSourceContext(core_kernel_classes_Class $collectionClass): RemoteSourceContext
    {
        $sourceUrl = (string) $collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_SOURCE_URI)
        );
        $uriPath = (string) $collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_URI_PATH)
        );
        $labelPath = (string) $collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_LABEL_PATH)
        );

        $parameters = [
            RemoteSourceContext::PARAM_SOURCE_URL => $sourceUrl,
            RemoteSourceContext::PARAM_URI_PATH => $uriPath,
            RemoteSourceContext::PARAM_LABEL_PATH => $labelPath,
            RemoteSourceContext::PARAM_PARSER => 'jsonpath',
        ];

        if ($this->isListsDependencyEnabled()) {
            $dependencyUriPath = (string) $collectionClass->getOnePropertyValue(
                $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_DEPENDENCY_ITEM_URI_PATH)
            );
            $parameters[RemoteSourceContext::PARAM_DEPENDENCY_URI_PATH] = $dependencyUriPath;
        }

        return new RemoteSourceContext($parameters);
    }

    private function isListsDependencyEnabled(): bool
    {
        return $this->featureFlagChecker->isEnabled(
            FeatureFlagCheckerInterface::FEATURE_FLAG_LISTS_DEPENDENCY_ENABLED
        );
    }
}

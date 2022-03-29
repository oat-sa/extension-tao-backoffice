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

namespace oat\taoBackOffice\model\lists;

use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\taoBackOffice\model\lists\Service\ListDeleter;
use oat\generis\model\resource\Repository\ClassRepository;
use oat\taoBackOffice\model\ListElement\Service\ListElementsDeleter;
use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\tao\model\Lists\Business\Specification\RemoteListClassSpecification;
use oat\tao\model\Lists\DataAccess\Repository\ParentPropertyListCachedRepository;
use oat\taoBackOffice\model\lists\Service\ListElementsUpdater;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class ListServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();
        $parameters = $configurator->parameters();

        $parameters->set('LOCAL_LIST_MAX_ITEMS', 1000);

        $services
            ->set(ListService::class, ListService::class)
            ->public()
            ->factory(ListService::class . '::singleton')
            ->call('setMaxItems', [
                env('LOCAL_LIST_MAX_ITEMS')
                    ->default('LOCAL_LIST_MAX_ITEMS')
                    ->int()
            ]);

        $services
            ->set(ListCreator::class, ListCreator::class)
            ->public()
            ->args(
                [
                    service(ListService::class),
                    service(RemoteListClassSpecification::class),
                ]
            );

        $services
            ->set(ListDeleter::class, ListDeleter::class)
            ->public()
            ->args(
                [
                    service(ListElementsDeleter::class),
                    service(RemoteListClassSpecification::class),
                    service(ParentPropertyListCachedRepository::class),
                    service(ClassRepository::class),
                ]
            );

        $services
            ->set(ListElementsUpdater::class, ListElementsUpdater::class)
            ->public()
            ->args(
                [
                    service(ValueCollectionService::class),
                ]
            );
    }
}

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

namespace oat\taoBackOffice\model\ListElement;

use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\taoBackOffice\model\ListElement\Service\ListElementsFinder;
use oat\taoBackOffice\model\ListElement\Service\ListElementsDeleter;
use oat\tao\model\Lists\DataAccess\Repository\RdfValueCollectionRepository;
use oat\tao\model\Lists\DataAccess\Repository\RdsValueCollectionRepository;
use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\tao\model\Lists\Business\Specification\RemoteListClassSpecification;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class ListElementServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();
        $parameters = $configurator->parameters();

        $parameters->set('LOCAL_LIST_ELEMENTS_LIMIT', 20);
        $parameters->set('REMOTE_LIST_ELEMENTS_LIMIT', 20);

        $services
            ->set(ListElementsFinder::class, ListElementsFinder::class)
            ->public()
            ->args(
                [
                    service(RemoteListClassSpecification::class),
                    service(ValueCollectionService::class),
                    env('LOCAL_LIST_ELEMENTS_LIMIT')
                        ->default('LOCAL_LIST_ELEMENTS_LIMIT')
                        ->int(),
                    env('REMOTE_LIST_ELEMENTS_LIMIT')
                        ->default('REMOTE_LIST_ELEMENTS_LIMIT')
                        ->int(),
                ]
            );

        $services
            ->set(ListElementsDeleter::class, ListElementsDeleter::class)
            ->public()
            ->call(
                'addRepository',
                [
                    service(RdfValueCollectionRepository::SERVICE_ID),
                ]
            )
            ->call(
                'addRepository',
                [
                    service(RdsValueCollectionRepository::SERVICE_ID),
                ]
            );
    }
}

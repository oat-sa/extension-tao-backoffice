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

namespace oat\taoMediaManager\test\unit\model;

use ArrayIterator;
use oat\generis\test\TestCase;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\taoBackOffice\model\lists\ListCreatedResponse;
use oat\taoBackOffice\model\lists\ListCreator;
use oat\taoBackOffice\model\lists\ListService;
use core_kernel_classes_Class;
use PHPUnit\Framework\MockObject\MockObject;

class ListCreatorTest extends TestCase
{
    /** @var ListService|MockObject */
    private $listService;

    /** @var ClassSpecificationInterface|MockObject */
    private $remoteListClassSpecification;

    /** @var ListCreator */
    private $sut;

    public function setUp(): void
    {
        $this->listService = $this->createMock(ListService::class);
        $this->remoteListClassSpecification = $this->createMock(ClassSpecificationInterface::class);

        $this->sut = new ListCreator($this->listService, $this->remoteListClassSpecification);
    }

    public function testCreateEmptyList(): void
    {
        $list = $this->createMock(core_kernel_classes_Class::class);
        $existingLists = [
            $this->createMock(core_kernel_classes_Class::class),
            $this->createMock(core_kernel_classes_Class::class),
            $this->createMock(core_kernel_classes_Class::class),
        ];

        $this->remoteListClassSpecification
            ->expects($this->at(0))
            ->method('isSatisfiedBy')
            ->willReturn(false);

        $this->remoteListClassSpecification
            ->expects($this->at(1))
            ->method('isSatisfiedBy')
            ->willReturn(false);

        $this->remoteListClassSpecification
            ->expects($this->at(2))
            ->method('isSatisfiedBy')
            ->willReturn(true);

        $this->listService
            ->method('getLists')
            ->willReturn($existingLists);

        $this->listService
            ->expects($this->once())
            ->method('createList')
            ->with(__('List') . ' 3')
            ->willReturn($list);

        $this->listService
            ->expects($this->once())
            ->method('createListElement')
            ->with($list, __('Element') . ' 1');

        $this->listService
            ->method('getListElements')
            ->with($list)
            ->willReturn(new ArrayIterator([]));

        $this->assertEquals(
            new ListCreatedResponse($list, []),
            $this->sut->createEmptyList()
        );
    }
}

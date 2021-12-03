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

use oat\generis\test\TestCase;
use oat\tao\model\Specification\ClassSpecificationInterface;
use oat\taoBackOffice\model\lists\ListCreatedResponse;
use oat\taoBackOffice\model\lists\ListCreator;
use oat\taoBackOffice\model\lists\ListService;
use core_kernel_classes_Class;

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

    /**
     * @dataProvider createEmptyListDataProvider
     */
    public function testCreateEmptyList(string $listName, array $existingLists): void
    {
        var_export($existingLists);

        $list = $this->createMock(core_kernel_classes_Class::class);

        $allLists = [];
        foreach ($existingLists as $previousListInfo) {
            $previousList = $previousListInfo[0];
            $isRemote = $previousListInfo[1];

            $this->remoteListClassSpecification
                ->method('isSatisfiedBy')
                ->with($previousList)
                ->willReturn($isRemote);

            $allLists[] = $previousList;
        }

        $this->listService
            ->method('getLists')
            ->willReturn($allLists);

        $this->listService
            ->method('createList')
            ->with($listName)
            ->willReturn($list);

        $this->listService
            ->expects($this->once())
            ->method('createListElement')
            ->with($list, __('Element').' 1');

        $this->listService
            ->method('getListElements')
            ->with($list)
            ->willReturn(new \ArrayIterator([]));

        $expected = new ListCreatedResponse($list, []);

        $this->assertEquals($expected, $this->sut->createEmptyList());
    }

    public function createEmptyListDataProvider(): array
    {
       return [
           'New list with no prior lists' => [
               'listName' => __('List') . ' 1',
               'existingLists' => [],
           ],
           'New list with a previous list' => [
               'listName' => __('List') . ' 2',
               'existingLists' => [
                   // [list, isRemote]
                   [$this->createMock(core_kernel_classes_Class::class), false],
               ],
           ],
           'New list with more than one previous list' => [
               'listName' => __('List') . ' 3',
               'existingLists' => [
                   [$this->createMock(core_kernel_classes_Class::class), false],
                   [$this->createMock(core_kernel_classes_Class::class), false],
               ],
           ],
           /* @fixme Not working
           'New list with remote and non-remote previous lists' => [
               'listName' => __('List') . ' 2',
               'existingLists' => [
                   [$this->createMock(core_kernel_classes_Class::class), false],
                   [$this->createMock(core_kernel_classes_Class::class), true],
               ],
           ]*/
       ];
    }
}

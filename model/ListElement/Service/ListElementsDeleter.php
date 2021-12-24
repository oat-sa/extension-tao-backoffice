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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoBackOffice\model\ListElement\Service;

use core_kernel_classes_Class;
use oat\tao\model\Lists\Business\Contract\ValueCollectionRepositoryInterface;
use oat\taoBackOffice\model\ListElement\Contract\ListElementsDeleterInterface;

class ListElementsDeleter implements ListElementsDeleterInterface
{
    /** @var ValueCollectionRepositoryInterface[] */
    private $repositories = [];

    public function addRepository(ValueCollectionRepositoryInterface $valueCollectionRepository): self
    {
        if (!in_array($valueCollectionRepository, $this->repositories, true)) {
            $this->repositories[] = $valueCollectionRepository;
        }

        return $this;
    }

    public function delete(core_kernel_classes_Class $list): void
    {
        foreach ($this->repositories as $repository) {
            if ($repository->isApplicable($list->getUri())) {
                $repository->delete($list->getUri());
            }
        }
    }
}

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

namespace oat\taoBackOffice\model\lists\Contract;

use oat\tao\model\Lists\DataAccess\Repository\ValueConflictException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application service to update elements for a list
 */
interface ListUpdaterInterface
{
    /**
     * @throws BadFunctionCallException if the payload contains too many items
     * @throws OverflowException if the list exceeds the allowed number of items
     * @throws ValueConflictException if element URIs are non-unique
     */
    public function updateByRequest(ServerRequestInterface $request): bool;
}

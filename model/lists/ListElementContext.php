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

use oat\tao\model\Context\AbstractContext;

class ListElementContext extends AbstractContext
{
    public const PARAM_LIMIT = 'limit';
    public const PARAM_LIST_URI = 'listUri';
    public const PARAM_PARENT_LIST_URIS = 'parentListUris';
    public const PARAM_PARENT_LIST_VALUES = 'parentListValues';

    protected function getSupportedParameters(): array
    {
        return [
            self::PARAM_LIMIT,
            self::PARAM_LIST_URI,
            self::PARAM_PARENT_LIST_URIS,
            self::PARAM_PARENT_LIST_VALUES,
        ];
    }

    protected function validateParameter(string $parameter, $parameterValue): void
    {
    }
}

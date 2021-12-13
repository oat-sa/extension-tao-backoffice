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

namespace oat\taoBackOffice\model\ListElement\Context;

use InvalidArgumentException;
use core_kernel_classes_Class;
use oat\tao\model\Context\AbstractContext;

class ListElementsFinderContext extends AbstractContext
{
    public const PARAMETER_TYPE = 'type';
    public const PARAMETER_LIST_CLASS = 'listClass';
    public const PARAMETER_LIMIT = 'limit';
    public const PARAMETER_OFFSET = 'offset';

    private const REQUIRED_PARAMETERS = [
        self::PARAMETER_LIST_CLASS,
    ];

    public function __construct(array $parameters)
    {
        $this->validateRequiredParameters($parameters);

        parent::__construct($parameters);
    }

    protected function getSupportedParameters(): array
    {
        return [
            self::PARAMETER_TYPE,
            self::PARAMETER_LIST_CLASS,
            self::PARAMETER_LIMIT,
            self::PARAMETER_OFFSET,
        ];
    }

    protected function validateParameter(string $parameter, $parameterValue): void
    {
        if ($parameter === self::PARAMETER_TYPE && is_string($parameterValue)) {
            return;
        }

        if ($parameter === self::PARAMETER_LIST_CLASS && $parameterValue instanceof core_kernel_classes_Class) {
            return;
        }

        if (
            in_array($parameter, [self::PARAMETER_LIMIT, self::PARAMETER_OFFSET], true)
            && is_int($parameterValue)
        ) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Context parameter %s is not valid.',
                $parameter
            )
        );
    }

    private function validateRequiredParameters(array $parameters): void
    {
        $missedRequiredParameters = array_diff_key(array_flip(self::REQUIRED_PARAMETERS), $parameters);

        if (!empty($missedRequiredParameters)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Some of the required parameters are missing: %s.',
                    implode(', ', $missedRequiredParameters)
                )
            );
        }
    }
}

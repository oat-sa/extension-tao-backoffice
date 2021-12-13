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

use oat\tao\model\Context\ContextInterface;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\taoBackOffice\model\ListElement\Context\ListElementsFinderContext;
use oat\taoBackOffice\model\ListElement\Contract\ListElementsFinderInterface;

class ListElementsFinderProxy implements ListElementsFinderInterface
{
    public const TYPE_LOCAL = 'local';
    public const TYPE_REMOTE = 'remote';

    /** @var ListElementsFinderInterface */
    private $listElementsFinder;

    /** @var int */
    private $localListElementsLimit;

    /** @var int */
    private $remoteListElementsLimit;

    public function __construct(
        ListElementsFinderInterface $listElementsFinder,
        int $localListElementsLimit,
        int $remoteListElementsLimit
    ) {
        $this->listElementsFinder = $listElementsFinder;
        $this->localListElementsLimit = $localListElementsLimit;
        $this->remoteListElementsLimit = $remoteListElementsLimit;
    }

    public function find(ContextInterface $context): ValueCollection
    {
        $type = $context->getParameter(ListElementsFinderContext::PARAMETER_TYPE, self::TYPE_LOCAL);

        if ($context->getParameter(ListElementsFinderContext::PARAMETER_LIMIT) === null) {
            $context->setParameter(
                ListElementsFinderContext::PARAMETER_LIMIT,
                $type === self::TYPE_LOCAL
                    ? $this->localListElementsLimit
                    : $this->remoteListElementsLimit
            );
        }

        return $this->listElementsFinder->find($context);
    }
}

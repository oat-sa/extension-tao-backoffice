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
 * Copyright (c) 2018-2022 (original work) Open Assessment Technologies SA.
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */

namespace oat\taoBackOffice\controller;

use Throwable;
use tao_actions_CommonModule;
use InvalidArgumentException;
use common_exception_MissingParameter;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\http\HttpJsonResponseTrait;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoBackOffice\model\routing\ResourceUrlBuilder;

class Redirector extends tao_actions_CommonModule
{
    use OntologyAwareTrait;
    use HttpJsonResponseTrait;

    private const PARAMETER_TASK_ID = 'taskId';

    /**
     * Redirect to a resource generated in a task.
     */
    public function redirectTaskToInstance(): void
    {
        $queryParams = $this->getPsrRequest()->getQueryParams();

        if (!isset($queryParams[self::PARAMETER_TASK_ID])) {
            throw new common_exception_MissingParameter(self::PARAMETER_TASK_ID, $this->getRequestURI());
        }

        $entity = $this->getTaskLog()->getByIdAndUser(
            $queryParams[self::PARAMETER_TASK_ID],
            $this->getSession()->getUserUri(),
            true // in Sync mode, task is archived straightaway
        );

        $uri = $entity->getResourceUriFromReport();
        $resource = $this->getResource($uri);

        try {
            $this->setSuccessJsonResponse($this->getResourceUrlBuilder()->buildUrl($resource));
        } catch (InvalidArgumentException $exception) {
            $this->logError($exception->getMessage());
            $this->setErrorJsonResponse(
                __('The requested resource does not exist or has been deleted'),
                202,
                [],
                202
            );
        } catch (Throwable $exception) {
            $this->logError($exception->getMessage());
            $this->setErrorJsonResponse(
                __('There was a problem redirecting to the requested resource'),
                500,
                [],
                500
            );
        }
    }

    private function getTaskLog(): TaskLogInterface
    {
        return $this->getPsrContainer()->get(TaskLogInterface::SERVICE_ID);
    }

    private function getResourceUrlBuilder(): ResourceUrlBuilder
    {
        return $this->getPsrContainer()->get(ResourceUrlBuilder::SERVICE_ID);
    }
}

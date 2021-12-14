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
 * Copyright (c) 2002-2008 (original work) Public Research Centre Henri Tudor & University of Luxembourg (under the project TAO & TAO2);
 *               2008-2010 (update and modification) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *               2013-2021 (update and modification) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoBackOffice\controller;

use tao_helpers_Uri;
use RuntimeException;
use common_Exception;
use common_exception_Error;
use oat\tao\helpers\Template;
use tao_actions_CommonModule;
use tao_helpers_Scriptloader;
use core_kernel_classes_Class;
use common_exception_BadRequest;
use tao_actions_form_RemoteList;
use common_ext_ExtensionException;
use oat\generis\model\data\Ontology;
use core_kernel_persistence_Exception;
use oat\tao\model\http\HttpJsonResponseTrait;
use oat\tao\model\Lists\Business\Domain\Value;
use oat\taoBackOffice\model\lists\ListCreator;
use oat\taoBackOffice\model\lists\ListService;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\taoBackOffice\model\lists\ListCreatedResponse;
use oat\tao\model\Lists\Business\Service\RemoteSource;
use oat\tao\model\Lists\Business\Domain\CollectionType;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\Lists\Business\Domain\RemoteSourceContext;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Service\RemoteSourcedListOntology;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\tao\model\Lists\DataAccess\Repository\ValueConflictException;
use oat\taoBackOffice\model\ListElement\Service\ListElementsFinderProxy;
use oat\taoBackOffice\model\ListElement\Context\ListElementsFinderContext;
use oat\taoBackOffice\model\ListElement\Contract\ListElementsFinderInterface;

class Lists extends tao_actions_CommonModule
{
    use HttpJsonResponseTrait;

    /** @var bool */
    private $isListsDependencyEnabled;

    /**
     * This REST endpoint:
     * - Returns the page with the lists for GET requests
     * - Creates a new list and returns its name and URI for POST requests
     *
     * @throws common_Exception
     * @throws common_ext_ExtensionException
     * @throws core_kernel_persistence_Exception
     */
    public function index(): void
    {
        if ($this->getPsrRequest()->getMethod() === 'POST') {
            if (!$this->isXmlHttpRequest()) {
                throw new common_exception_BadRequest('wrong request mode');
            }

            $createdResponse = $this->getListCreator()->createEmptyList();
            $this->setSuccessJsonResponse($createdResponse, 201);

            return;
        }

        tao_helpers_Scriptloader::addCssFile(Template::css('lists.css', 'tao'));
        $this->defaultData();

        $this->setData('lists', $this->getListData());
        $this->setView('Lists/index.tpl');
    }

    /**
     * @throws common_Exception
     * @throws common_ext_ExtensionException
     * @throws core_kernel_persistence_Exception
     */
    public function remote(ValueCollectionService $valueCollectionService, RemoteSource $remoteSource): void
    {
        tao_helpers_Scriptloader::addCssFile(Template::css('lists.css', 'tao'));

        $this->defaultData();

        $remoteListFormFactory = new tao_actions_form_RemoteList(
            [],
            [
                tao_actions_form_RemoteList::IS_LISTS_DEPENDENCY_ENABLED => $this->isListsDependencyEnabled(),
            ]
        );
        $remoteListForm = $remoteListFormFactory->getForm();

        if ($remoteListForm === null) {
            throw new RuntimeException('Impossible to create remote sourced list form');
        }

        if ($remoteListForm->isSubmited()) {
            if ($remoteListForm->isValid()) {
                $values = $remoteListForm->getValues();
                $newList = $this->createList($values);

                try {
                    $this->sync($valueCollectionService, $remoteSource, $newList);

                    // @FIXME >>> Refactor this part as this is a hotfix
                    $listElements = $this->getListElementsFinder()->find(
                        $this->createListElementsFinderContext($newList)
                    );

                    $this->setSuccessJsonResponse(
                        new ListCreatedResponse($newList, iterator_to_array($listElements)),
                        201
                    );

                    return;
                    // @FIXME <<< Refactor this part as this is a hotfix
                } catch (ValueConflictException $exception) {
                    $this->returnError(
                        $exception->getMessage() . __(' Probably given list was already imported.')
                    );

                    return;
                } catch (RuntimeException $exception) {
                    throw $exception;
                } finally {
                    if (isset($exception)) {
                        $this->removeList(tao_helpers_Uri::encode($newList->getUri()));
                    }
                }
            }
        } else {
            $newListLabel = __('List') . ' ' . (count($this->getListService()->getLists()) + 1);
            $remoteListForm->getElement(tao_actions_form_RemoteList::FIELD_NAME)->setValue($newListLabel);
        }

        $this->setData('form', $remoteListForm->render());
        $this->setData('lists', $this->getListData(true));
        $this->setView('RemoteLists/index.tpl');
    }

    /**
     * @throws common_Exception
     */
    public function reloadRemoteList(ValueCollectionService $valueCollectionService, RemoteSource $remoteSource): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $saved = false;
        $uri = $_POST['uri'] ?? null;

        if ($uri !== null) {
            $decodedUri = tao_helpers_Uri::decode($uri);

            $this->sync(
                $valueCollectionService,
                $remoteSource,
                $this->getListService()->getList($decodedUri)
            );

            $saved = true;
        }

        $this->returnJson(['saved' => $saved]);
    }

    public function sync(
        ValueCollectionService $valueCollectionService,
        RemoteSource $remoteSource,
        core_kernel_classes_Class $collectionClass
    ): void {
        $context = $this->createRemoteSourceContext($collectionClass);
        $collection = new ValueCollection(
            $collectionClass->getUri(),
            ...iterator_to_array($remoteSource->fetchByContext($context))
        );

        $result = $valueCollectionService->persist($collection);

        if (!$result) {
            throw new RuntimeException('Sync was not successful');
        }
    }

    /**
     * @throws common_Exception
     * @throws common_exception_Error
     */
    public function getListsData(): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $data = [];

        foreach ($this->getListService()->getLists() as $listClass) {
            $data[] = $this->getListService()->toTree($listClass);
        }

        $this->returnJson(
            [
                'data' => __('Lists'),
                'attributes' => ['class' => 'node-root'],
                'children' => $data,
                'state' => 'open',
            ]
        );
    }

    /**
     * @throws common_Exception
     * @throws core_kernel_persistence_Exception
     */
    public function getListElements(): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $data = [];

        if ($this->hasGetParameter('listUri')) {
            $listUri = tao_helpers_Uri::decode($this->getGetParameter('listUri'));
            $list = $this->getListService()->getList($listUri);

            if ($list !== null) {
                $listElements = $this->getListElementsFinder()->find(
                    $this->createListElementsFinderContext($list)
                );

                foreach ($listElements->getIterator() as $listElement) {
                    $data[tao_helpers_Uri::encode($listElement->getUri())] = $listElement->getLabel();
                }
            }
        }

        $this->returnJson($data);
    }

    public function saveLists(ValueCollectionService $valueCollectionService): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        if (!$this->hasRequestParameter('uri')) {
            $this->returnJson(['saved' => false]);

            return;
        }

        $listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('uri')));

        if ($listClass === null) {
            $this->returnJson(['saved' => false]);

            return;
        }

        // use $_POST instead of getRequestParameters to prevent html encoding
        $payload = $_POST;
        unset($payload['uri']);

        if (isset($payload['label'])) {
            $listClass->setLabel($payload['label']);
            unset($payload['label']);
        }

        $elements = $valueCollectionService->findAll(
            new ValueCollectionSearchInput(
                (new ValueCollectionSearchRequest())->setValueCollectionUri($listClass->getUri())
            )
        );

        foreach ($payload as $key => $value) {
            if (preg_match('/^list-element_/', $key)) {
                $encodedUri = preg_replace('/^list-element_[0-9]+_/', '', $key);
                $uri = tao_helpers_Uri::decode($encodedUri);
                $newUriValue = trim($payload["uri_$key"] ?? '');
                $element = $elements->extractValueByUri($uri);

                if ($element === null) {
                    $elements->addValue(new Value(null, $newUriValue, $value));

                    continue;
                }

                $element->setLabel($value);

                if ($newUriValue) {
                    $element->setUri($newUriValue);
                }
            }
        }

        try {
            $this->returnJson(['saved' => $valueCollectionService->persist($elements)]);
        } catch (ValueConflictException $exception) {
            $this->returnJson(
                [
                    'saved' => false,
                    'errors' => [
                        __('The list should contain unique URIs'),
                    ],
                ]
            );
        }
    }

    /**
     * @throws common_Exception
     * @throws core_kernel_persistence_Exception
     */
    public function create(): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $response = [];

        if ($this->hasRequestParameter('classUri')) {
            $listService = $this->getListService();
            $type = $this->getRequestParameter('type');

            if ($type === 'class' && $this->getRequestParameter('classUri') === 'root') {
                $createdResource = $listService->createList();
            } elseif ($type === 'instance') {
                $classUri = tao_helpers_Uri::decode($this->getRequestParameter('classUri'));
                $listClass = $listService->getList($classUri);

                if ($listClass !== null) {
                    $listService->createListElement($listClass);
                    $createdResource = iterator_to_array($listService->getListElements($listClass))[0] ?? null;
                }
            }

            if (isset($createdResource)) {
                $response['label'] = $createdResource->getLabel();
                $response['uri'] = tao_helpers_Uri::encode($createdResource->getUri());
            }
        }

        $this->returnJson($response);
    }

    public function rename(): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $data = ['renamed' => false];

        if ($this->hasRequestParameter('uri') && $this->hasRequestParameter('newName')) {
            $listService = $this->getListService();
            $newName = $this->getRequestParameter('newName');

            if ($this->hasRequestParameter('classUri')) {
                $classUri = tao_helpers_Uri::decode($this->getRequestParameter('classUri'));
                $listClass = $listService->getList($classUri);
                $resourceToRename = $listService->getListElement(
                    $listClass,
                    tao_helpers_Uri::decode($this->getRequestParameter('uri'))
                );
            } else {
                $classUri = tao_helpers_Uri::decode($this->getRequestParameter('uri'));
                $resourceToRename = $listService->getList($classUri);
            }

            if ($resourceToRename !== null) {
                $resourceToRename->setLabel($newName);
                $data['renamed'] = true;
            }
        }

        $this->returnJson($data);
    }

    public function removeList(string $uri = null): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $deleted = false;

        if ($uri !== null) {
            $decodedUri = tao_helpers_Uri::decode($uri);

            $deleted = $this->getListService()->removeList(
                $this->getListService()->getList($decodedUri)
            );
        }

        $this->returnJson(['deleted' => $deleted]);
    }

    public function removeListElement(): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $deleted = false;

        if ($this->hasRequestParameter('uri')) {
            $deleted = $this->getListService()->removeListElement(
                $this->getOntology()->getResource(
                    tao_helpers_Uri::decode($this->getRequestParameter('uri'))
                )
            );
        }

        $this->returnJson(['deleted' => $deleted]);
    }

    private function createList(array $values): core_kernel_classes_Class
    {
        $class = $this->getListService()->createList($values[tao_actions_form_RemoteList::FIELD_NAME]);

        $propertyType = $class->getProperty(CollectionType::TYPE_PROPERTY);
        $propertyRemote = $class->getProperty((string) CollectionType::remote());
        $class->setPropertyValue($propertyType, $propertyRemote);

        $propertySource = $class->getProperty(RemoteSourcedListOntology::PROPERTY_SOURCE_URI);
        $class->setPropertyValue($propertySource, $values[tao_actions_form_RemoteList::FIELD_SOURCE_URL]);

        $propertySource = $class->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_LABEL_PATH);
        $class->setPropertyValue($propertySource, $values[tao_actions_form_RemoteList::FIELD_ITEM_LABEL_PATH]);

        $propertySource = $class->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_URI_PATH);
        $class->setPropertyValue($propertySource, $values[tao_actions_form_RemoteList::FIELD_ITEM_URI_PATH]);

        if ($this->isListsDependencyEnabled()) {
            $propertySource = $class->getProperty(RemoteSourcedListOntology::PROPERTY_DEPENDENCY_ITEM_URI_PATH);
            $class->setPropertyValue(
                $propertySource,
                $values[tao_actions_form_RemoteList::FIELD_DEPENDENCY_ITEM_URI_PATH]
            );
        }

        return $class;
    }

    /**
     * @throws core_kernel_persistence_Exception
     */
    private function getListData(bool $showRemoteLists = false): array
    {
        $listService = $this->getListService();
        $listElementsFinder = $this->getListElementsFinder();
        $lists = [];

        foreach ($listService->getLists() as $listClass) {
            if ($listService->isRemote($listClass) !== $showRemoteLists) {
                continue;
            }

            $elements = [];
            $listElements = $listElementsFinder->find(
                $this->createListElementsFinderContext($listClass)
            );

            foreach ($listElements->getIterator() as $index => $listElement) {
                $elements[$index] = [
                    'uri' => tao_helpers_Uri::encode($listElement->getUri()),
                    'label' => $listElement->getLabel(),
                ];
            }

            ksort($elements);

            $lists[] = [
                'uri' => tao_helpers_Uri::encode($listClass->getUri()),
                'label' => $listClass->getLabel(),
                'editable' => $listService->isEditable($listClass),
                'elements' => $elements,
                'totalCount' => $listElements->getTotalCount(),
            ];
        }

        return $lists;
    }

    private function createRemoteSourceContext(core_kernel_classes_Class $collectionClass): RemoteSourceContext
    {
        $sourceUrl = (string) $collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_SOURCE_URI)
        );
        $uriPath = (string) $collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_URI_PATH)
        );
        $labelPath = (string) $collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_LABEL_PATH)
        );

        $parameters = [
            RemoteSourceContext::PARAM_SOURCE_URL => $sourceUrl,
            RemoteSourceContext::PARAM_URI_PATH => $uriPath,
            RemoteSourceContext::PARAM_LABEL_PATH => $labelPath,
            RemoteSourceContext::PARAM_PARSER => 'jsonpath',
        ];

        if ($this->isListsDependencyEnabled()) {
            $dependencyUriPath = (string) $collectionClass->getOnePropertyValue(
                $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_DEPENDENCY_ITEM_URI_PATH)
            );
            $parameters[RemoteSourceContext::PARAM_DEPENDENCY_URI_PATH] = $dependencyUriPath;
        }

        return new RemoteSourceContext($parameters);
    }

    private function createListElementsFinderContext(core_kernel_classes_Class $listClass): ListElementsFinderContext
    {
        $parameters = [
            ListElementsFinderContext::PARAMETER_LIST_CLASS => $listClass,
        ];

        if ($this->hasGetParameter('offset')) {
            $parameters[ListElementsFinderContext::PARAMETER_OFFSET] = (int) $this->getGetParameter('offset');
        }

        if ($this->hasGetParameter('limit')) {
            $parameters[ListElementsFinderContext::PARAMETER_LIMIT] = (int) $this->getGetParameter('limit');
        }

        return new ListElementsFinderContext($parameters);
    }

    private function isListsDependencyEnabled(): bool
    {
        if (!isset($this->isListsDependencyEnabled)) {
            $this->isListsDependencyEnabled = $this->getFeatureFlagChecker()->isEnabled(
                FeatureFlagCheckerInterface::FEATURE_FLAG_LISTS_DEPENDENCY_ENABLED
            );
        }

        return $this->isListsDependencyEnabled;
    }

    private function getFeatureFlagChecker(): FeatureFlagCheckerInterface
    {
        return $this->getPsrContainer()->get(FeatureFlagChecker::class);
    }

    private function getListService(): ListService
    {
        return $this->getPsrContainer()->get(ListService::class);
    }

    private function getListCreator(): ListCreator
    {
        return $this->getPsrContainer()->get(ListCreator::class);
    }

    private function getOntology(): Ontology
    {
        return $this->getPsrContainer()->get(Ontology::SERVICE_ID);
    }

    private function getListElementsFinder(): ListElementsFinderInterface
    {
        return $this->getPsrContainer()->get(ListElementsFinderProxy::class);
    }
}

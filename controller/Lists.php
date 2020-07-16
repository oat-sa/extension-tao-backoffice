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
 *               2013-2018 (update and modification) Open Assessment Technologies SA;
 *
 */

namespace oat\taoBackOffice\controller;

use common_exception_BadRequest;
use common_ext_ExtensionException as ExtensionException;
use core_kernel_classes_Class as RdfClass;
use core_kernel_classes_Property as RdfProperty;
use core_kernel_persistence_Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\helpers\Template;
use oat\tao\model\Lists\Business\Domain\CollectionType;
use oat\tao\model\Lists\Business\Domain\Value;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Service\RemoteSource;
use oat\tao\model\Lists\Business\Service\RemoteSourcedListOntology;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\tao\model\Lists\DataAccess\Repository\ValueConflictException;
use oat\tao\model\TaoOntology;
use oat\taoBackOffice\model\lists\ListService;
use RuntimeException;
use tao_actions_CommonModule;
use tao_actions_form_List;
use tao_actions_form_RemoteList;
use tao_helpers_Scriptloader;
use tao_helpers_Uri;
use tao_models_classes_LanguageService;

/**
 * This controller provide the actions to manage the lists of data
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package taoBackOffice
 *
 */
class Lists extends tao_actions_CommonModule
{
    use OntologyAwareTrait;

    private const REMOTE_LIST_PREVIEW_LIMIT = 20;

    /**
     * Show the list of users
     * @return void
     */
    public function index()
    {
        tao_helpers_Scriptloader::addCssFile(Template::css('lists.css', 'tao'));
        $this->defaultData();

        $myAdderFormContainer = new tao_actions_form_List();
        $myForm = $myAdderFormContainer->getForm();

        if ($myForm->isSubmited()) {
            if ($myForm->isValid()) {
                $values = $myForm->getValues();
                $newList = $this->getListService()->createList($values['label']);
                $i = 0;
                while ($i < $values['size']) {
                    $this->getListService()->createListElement($newList, __('element') . ' ' . ($i + 1));
                    $i++;
                }
            }
        } else {
            $myForm->getElement('label')->setValue(__('List') . ' ' . (count($this->getListService()->getLists()) + 1));
        }
        $this->setData('form', $myForm->render());

        $this->setData('lists', $this->getListData());
        $this->setView('Lists/index.tpl');
    }

    /**
     * @param ValueCollectionService $valueCollectionService
     * @param RemoteSource           $remoteSource
     *
     * @throws ExtensionException
     * @throws core_kernel_persistence_Exception
     */
    public function remote(
        ValueCollectionService $valueCollectionService,
        RemoteSource $remoteSource
    ): void {
        tao_helpers_Scriptloader::addCssFile(Template::css('lists.css', 'tao'));

        $this->defaultData();

        $remoteListFormFactory = new tao_actions_form_RemoteList();
        $remoteListForm = $remoteListFormFactory->getForm();

        if ($remoteListForm === null) {
            throw new RuntimeException('Impossible to create remote sourced list form');
        }

        if ($remoteListForm->isSubmited()) {
            if ($remoteListForm->isValid()) {
                $values = $remoteListForm->getValues();

                $newList = $this->createList(
                    $values[tao_actions_form_RemoteList::FIELD_NAME],
                    $values[tao_actions_form_RemoteList::FIELD_SOURCE_URL],
                    $values[tao_actions_form_RemoteList::FIELD_ITEM_LABEL_PATH],
                    $values[tao_actions_form_RemoteList::FIELD_ITEM_URI_PATH]
                );

                try {
                    $this->sync($valueCollectionService, $remoteSource, $newList);
                } catch (RuntimeException $exception) {
                    $this->removeList(tao_helpers_Uri::encode($newList->getUri()));

                    throw $exception;
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

    private function createList(string $label, string $source, string $labelPath, string $uriPath): RdfClass
    {
        $class = $this->getListService()->createList($label);

        $propertyType = new RdfProperty(CollectionType::TYPE_PROPERTY);
        $propertyRemote = new RdfProperty((string)CollectionType::remote());
        $class->setPropertyValue($propertyType, $propertyRemote);

        $propertySource = new RdfProperty(RemoteSourcedListOntology::PROPERTY_SOURCE_URI);
        $class->setPropertyValue($propertySource, $source);

        $propertySource = new RdfProperty(RemoteSourcedListOntology::PROPERTY_ITEM_LABEL_PATH);
        $class->setPropertyValue($propertySource, $labelPath);

        $propertySource = new RdfProperty(RemoteSourcedListOntology::PROPERTY_ITEM_URI_PATH);
        $class->setPropertyValue($propertySource, $uriPath);

        return $class;
    }

    /**
     * @param ValueCollectionService $valueCollectionService
     * @param RemoteSource           $remoteSource
     * @param RdfClass               $collectionClass
     *
     * @throws core_kernel_persistence_Exception
     */
    public function sync(
        ValueCollectionService $valueCollectionService,
        RemoteSource $remoteSource,
        RdfClass $collectionClass
    ): void {
        $sourceUrl = (string)$collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_SOURCE_URI)
        );
        $uriPath   = (string)$collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_URI_PATH)
        );
        $labelPath = (string)$collectionClass->getOnePropertyValue(
            $collectionClass->getProperty(RemoteSourcedListOntology::PROPERTY_ITEM_LABEL_PATH)
        );

        $collection = new ValueCollection(
            $collectionClass->getUri(),
            ...iterator_to_array($remoteSource->fetch($sourceUrl, $uriPath, $labelPath, 'jsonpath'))
        );

        $result = $valueCollectionService->persist($collection);

        if (!$result) {
            throw new RuntimeException('Sync was not successful');
        }
    }

    /**
     * Returns all lists with all values for all lists
     *
     * @param bool $showRemoteLists
     *
     * @return array
     * @throws core_kernel_persistence_Exception
     */
    private function getListData(bool $showRemoteLists = false): array
    {
        $listService = $this->getListService();
        $lists = [];
        foreach ($listService->getLists() as $listClass) {
            if ($listService->isRemote($listClass) !== $showRemoteLists) {
                continue;
            }

            $elements = [];
            foreach (
                $listService->getListElements(
                    $listClass,
                    true,
                    $showRemoteLists ? self::REMOTE_LIST_PREVIEW_LIMIT : 0
                ) as $index => $listElement
            ) {
                $elements[$index] = [
                    'uri'       => tao_helpers_Uri::encode($listElement->getUri()),
                    'label'     => $listElement->getLabel()
                ];
                ksort($elements);
            }

            if ($showRemoteLists && count($elements) === self::REMOTE_LIST_PREVIEW_LIMIT) {
                $elements[] = [
                    'uri' => '',
                    'label' => '...',
                ];
            }

            $lists[] = [
                'uri'       => tao_helpers_Uri::encode($listClass->getUri()),
                'label'     => $listClass->getLabel(),
                // The Language list should not be editable.
                // @todo Make two different kind of lists: system list that are not editable and usual list.
                'editable'  => $listClass->isSubClassOf($this->getClass(TaoOntology::CLASS_URI_LIST)) && $listClass->getUri() !== tao_models_classes_LanguageService::CLASS_URI_LANGUAGES,
                'elements'  => $elements
            ];
        }
        return $lists;
    }

    /**
     * get the JSON data to populate the tree widget
     *
     * @throws \common_exception_Error
     * @throws common_exception_BadRequest
     */
    public function getListsData()
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }
        $data = [];
        foreach ($this->getListService()->getLists() as $listClass) {
            $data[] = $this->getListService()->toTree($listClass);
        }
        $this->returnJson([
            'data'      => __('Lists'),
            'attributes' => ['class' => 'node-root'],
            'children'  => $data,
            'state'     => 'open'
        ]);
    }

    /**
     * get the elements in JSON of the list in parameter
     * @throws common_exception_BadRequest
     * @return void
     */
    public function getListElements()
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }
        $data = [];
        if ($this->hasRequestParameter('listUri')) {
            $list = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('listUri')));
            if (!is_null($list)) {
                $isRemote = $this->getListService()->isRemote($list);

                $limit    = $isRemote
                    ? self::REMOTE_LIST_PREVIEW_LIMIT
                    : 0;

                foreach ($this->getListService()->getListELements($list, true, $limit) as $listElement) {
                    $data[tao_helpers_Uri::encode($listElement->getUri())] = $listElement->getLabel();
                }

                if ($isRemote && count($data) === self::REMOTE_LIST_PREVIEW_LIMIT) {
                    $data[''] = '...';
                }
            }
        }
        $this->returnJson($data);
    }


    /**
     * Save a list and it's elements
     *
     * @param ValueCollectionService $valueCollectionService
     *
     * @return void
     *
     * @throws common_exception_BadRequest
     */
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
        if (null === $listClass) {
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
                (new ValueCollectionSearchRequest())
                    ->setValueCollectionUri($listClass->getUri())
            )
        );

        foreach ($payload as $key => $value) {
            if (preg_match('/^list-element_/', $key)) {
                $encodedUri = preg_replace('/^list-element_[0-9]+_/', '', $key);
                $uri        = tao_helpers_Uri::decode($encodedUri);

                $newUriValue = trim($payload["uri_$key"] ?? '');

                $element = $elements->extractValueByUri($uri);

                if (null === $element) {
                    $elements->addValue(new Value(null, $newUriValue, $value));
                } else {
                    $element->setLabel($value);

                    if ($newUriValue) {
                        $element->setUri($newUriValue);
                    }
                }

            }
        }

        try {
            $this->returnJson(['saved' => $valueCollectionService->persist($elements)]);
        } catch (ValueConflictException $exception) {
            $this->returnJson(['saved' => false, 'errors' => [__('The list should contain unique URIs')]]);
        }
    }

    /**
     * Create a list or a list element
     * @throws common_exception_BadRequest
     * @return void
     */
    public function create()
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $response = [];
        if ($this->getRequestParameter('classUri')) {
            if ($this->getRequestParameter('type') === 'class' && $this->getRequestParameter('classUri') ==='root') {
                $listClass = $this->getListService()->createList();
                if (!is_null($listClass)) {
                    $response['label']  = $listClass->getLabel();
                    $response['uri']    = tao_helpers_Uri::encode($listClass->getUri());
                }
            }

            if ($this->getRequestParameter('type') === 'instance') {
                $listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('classUri')));
                if (!is_null($listClass)) {
                    $listElt = $this->getListService()->createListElement($listClass);
                    if (!is_null($listElt)) {
                        $response['label']  = $listElt->getLabel();
                        $response['uri']    = tao_helpers_Uri::encode($listElt->getUri());
                    }
                }
            }
        }
        $this->returnJson($response);
    }

    /**
     * Rename a list node: change the label of a resource
     * Render the json response with the renamed status
     * @throws common_exception_BadRequest
     * @return void
     */
    public function rename()
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $data = ['renamed' => false];

        if ($this->hasRequestParameter('uri') && $this->hasRequestParameter('newName')) {
            if ($this->hasRequestParameter('classUri')) {
                $listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('classUri')));
                $listElt = $this->getListService()->getListElement($listClass, tao_helpers_Uri::decode($this->getRequestParameter('uri')));
                if (!is_null($listElt)) {
                    $listElt->setLabel($this->getRequestParameter('newName'));
                    if ($listElt->getLabel() == $this->getRequestParameter('newName')) {
                        $data['renamed'] = true;
                    }
                }
            } else {
                $listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('uri')));
                if (!is_null($listClass)) {
                    $listClass->setLabel($this->getRequestParameter('newName'));
                    if ($listClass->getLabel() == $this->getRequestParameter('newName')) {
                        $data['renamed'] = true;
                    }
                }
            }
        }
        $this->returnJson($data);
    }

    /**
     * Remove the list in parameter
     *
     * @param string|null $uri
     *
     * @return void
     * @throws common_exception_BadRequest
     */
    public function removeList(?string $uri = null): void
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }

        $deleted = false;

        if (null !== $uri) {
            $decodedUri = tao_helpers_Uri::decode($uri);

            $deleted = $this->getListService()->removeList(
                $this->getListService()->getList($decodedUri)
            );
        }

        $this->returnJson(['deleted' => $deleted]);
    }

    /**
     * Remove the list element in parameter
     * @throws common_exception_BadRequest
     * @return void
     */
    public function removeListElement()
    {
        if (!$this->isXmlHttpRequest()) {
            throw new common_exception_BadRequest('wrong request mode');
        }
        $deleted = false;

        if ($this->hasRequestParameter('uri')) {
            $deleted = $this->getListService()->removeListElement(
                $this->getResource(tao_helpers_Uri::decode($this->getRequestParameter('uri')))
            );
        }
        $this->returnJson(['deleted' => $deleted]);
    }

    /**
     * @return ListService
     */
    protected function getListService()
    {
        return ListService::singleton();
    }
}

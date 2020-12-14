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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoBackOffice\model\lists;

use core_kernel_classes_Class as RdfClass;
use core_kernel_persistence_Exception;
use oat\generis\model\kernel\uri\UriProvider;
use oat\tao\model\Lists\Business\Domain\Value;
use oat\tao\model\Lists\Business\Domain\ValueCollection;
use oat\tao\model\Lists\Business\Domain\ValueCollectionSearchRequest;
use oat\tao\model\Lists\Business\Input\ValueCollectionDeleteInput;
use oat\tao\model\Lists\Business\Input\ValueCollectionSearchInput;
use oat\tao\model\Lists\Business\Service\RemoteSourcedListOntology;
use oat\tao\model\Lists\Business\Service\ValueCollectionService;
use oat\tao\model\TaoOntology;
use tao_models_classes_LanguageService;
use tao_models_classes_ListService;

/**
 * Class ListService
 */
class ListService extends tao_models_classes_ListService
{
    /**
     * Whenever or not a list is editable
     * The Language list should not be editable.
     *
     * @param RdfClass $listClass
     *
     * @return boolean
     * @todo Make two different kind of lists: system list that are not editable and usual list.
     */
    public function isEditable(RdfClass $listClass)
    {
        return $listClass->isSubClassOf($this->getClass(TaoOntology::CLASS_URI_LIST))
            && $listClass->getUri() !== tao_models_classes_LanguageService::CLASS_URI_LANGUAGES;
    }

    public function getListElement(RdfClass $listClass, $uri)
    {
        $request = new ValueCollectionSearchRequest();
        $request->setValueCollectionUri($listClass->getUri())->setUris($uri);

        $result = $this->getValueService()->findAll(
            new ValueCollectionSearchInput($request)
        );

        return $result->count() === 0
            ? null
            : iterator_to_array($result->getIterator())[0];
    }

    public function getListElements(RdfClass $listClass, $sort = true, $limit = 0)
    {
        $request = new ValueCollectionSearchRequest();
        $request->setValueCollectionUri($listClass->getUri());
        if ($limit) {
            $request->setLimit($limit);
        }

        $result = $this->getValueService()->findAll(
            new ValueCollectionSearchInput($request)
        );

        return $result->getIterator();
    }

    /**
     * @param RdfClass $listClass
     *
     * @return bool
     */
    public function removeList(RdfClass $listClass)
    {
        $this->getValueService()->delete(
            new ValueCollectionDeleteInput($listClass->getUri())
        );


        return $listClass->delete();
    }

    public function createListElement(RdfClass $listClass, $label = '')
    {
        $newUri = $this->createUri();

        $valueCollection = new ValueCollection(
            $listClass->getUri(),
            new Value(null, $newUri, $label)
        );

        $this->getValueService()->persist($valueCollection);
    }

    private function getValueService(): ValueCollectionService
    {
        return $this->getServiceLocator()->get(ValueCollectionService::class);
    }

    private function createUri(): string
    {
        return $this->getServiceLocator()->get(UriProvider::class)->provide();
    }

    /**
     * @param RdfClass $listClass
     *
     * @return bool
     * @throws core_kernel_persistence_Exception
     */
    public function isRemote(RdfClass $listClass): bool
    {
        $type = $listClass->getOnePropertyValue(
            $listClass->getProperty(RemoteSourcedListOntology::PROPERTY_LIST_TYPE)
        );

        return $type && ($type->getUri() === RemoteSourcedListOntology::LIST_TYPE_REMOTE);
    }
}

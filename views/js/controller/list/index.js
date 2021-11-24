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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */
define([
    'jquery',
    'i18n',
    'uri',
    'util/url',
    'ui/feedback',
    'ui/dialog/confirm',
    'layout/section',
    'css!taoBackOfficeCss/list'
], function ($, __, Uri, urlUtil, feedback, dialogConfirm, section) {
    'use strict';

    function findListContainer(uri) {
        return $(`#list-data_${uri}`);
    }

    function clearUri(value) {
        return value.replace(/^list-element_[0-9]+_/, '');
    }

    function createEditUriCheckbox(id) {
        const $checkbox = $('<input>')
            .attr('type', 'checkbox')
            .attr('id', id)
            .change(handleEditCheckboxStateChange);

        const $label = $('<label>')
            .attr('for', id)
            .text(__('Edit URI'))
            .focus();

        return $('<span>')
            .addClass('lft edit-uri')
            .append($checkbox, $label);
    }

    function addSquareBtn(title, icon, $listToolBar, position='rgt') {
        const $btn = $('<button>', {
            'class': `btn-info small ${position} icon-${icon}`,
            title: __(title) }
        );
        $listToolBar.append($btn);

        return $btn;
    }

    function transformListElement($element) {
        return createListElement($element.attr('id'), $element.text());
    }

    function createNewListElement(elementId) {
        return createListElement(`list-element_${elementId}_`);
    }

    function createListElement(name, value = '') {
        return $(`<div class='list-element'>
            <div class='list-element'>
                <div class='list-element__input-container'>
                    <input type='text' name='${name}' value='${value}' />
                    <div class='list-element__input-container__uri'>
                        <label for='uri_${name}' class='title'>URI</label>
                        <input id='uri_${name}' type='text' name='uri_${name}' value='${Uri.decode(clearUri(name))}'>
                    </div>
                </div>
                <span class='icon-checkbox-crossed list-element-delete-btn'>
            </div>
        </div>`);
    }

    function handleEditCheckboxStateChange() {
        findListContainer($(this).attr('id')).toggleClass('with-uri');
    }

    function handleEditList (uriList) {
        const uri = uriList || $(this).data('uri');
        const $listContainer = findListContainer(uri);

        let $listForm       = $listContainer.find('form');
        const $listTitleBar = $listContainer.find('.container-title h6');
        const $listToolBar  = $listContainer.find('.data-container-footer').empty();
        let $listSaveBtn;
        let $listNewBtn;

        if (!$listForm.length) {
            let nextElementId;

            $listForm = $('<form>');
            $listContainer.wrapInner($listForm);
            $listContainer.find('form').append(`<input type='hidden' name='uri' value='${uri}' />`);

            const $labelEdit = $(`<input type='text' name='label' value=''/>`).val($listTitleBar.text());
            $listTitleBar.closest('.container-title').html($labelEdit);
            $labelEdit.focus();

            nextElementId = $listContainer.find('.list-element')
                .replaceWith(function () {
                    return transformListElement($(this));
                })
                .length;

            $listSaveBtn = addSquareBtn(__('Save list'), 'save', $listToolBar);
            $listSaveBtn.on('click', function () {
                $.postJson(
                    saveUrl,
                    $(this).closest('form').serializeArray(),
                    response => {
                        if (response.saved) {
                            feedback().success(__('List saved'));
                            section.get('taoBo_list').loadContentBlock(urlUtil.route('index', 'Lists', 'taoBackOffice'));
                        } else {
                            const errors = (response.errors || []).length
                                ? `<ul><li>${response.errors.join('</li><li>')}</li></ul>`
                                : '';

                            feedback().error(
                                `${__('List not saved')}${errors}`,
                                {encodeHtml: false}
                            );
                        }
                    }
                );
                return false;
            });

            $listNewBtn = addSquareBtn('New element', 'add', $listToolBar);
            $listNewBtn.click(function () {
                const $list = $(this).closest('form').find('ol');

                $list.append($('<li>').append(createNewListElement(nextElementId++)))
                    .closest('.container-content').scrollTop($list.height());

                return false;
            });

            $listToolBar.append(createEditUriCheckbox(uri));

            $listToolBar.append();
        }

        $listContainer.on('click', '.list-element-delete-btn', function () {
            const $element = $(this).closest('li');
            const $input   = $element.find('input:text');
            const eltUri   = clearUri($input.attr('name'));

            const deleteLocalElement = () => {
                $element.remove();
                feedback().success(__('Element deleted'));
            };

            const deleteServerAndLocalElement = () => {
                $.postJson(
                    delEltUrl,
                    { uri : eltUri },
                    response => {
                        if (response.deleted) {
                            deleteLocalElement();
                        } else {
                            feedback().error(__('Element not deleted'));
                        }
                    }
                );
            };

            const deleteElement = () => {
                if (eltUri) {
                    deleteServerAndLocalElement();
                } else {
                    deleteLocalElement();
                }
            };

            if ($input.val() === '') {
                deleteElement();
            } else {
                dialogConfirm(
                    __('Please confirm you want to delete this list element.'),
                    deleteElement
                );
            }
        });
    };

    return {

        /**
         * The list controller entrypoint
         */
        start() {

            const saveUrl    = urlUtil.route('saveLists', 'Lists', 'taoBackOffice');
            const delListUrl = urlUtil.route('removeList', 'Lists', 'taoBackOffice');
            const reloadListUrl = urlUtil.route('reloadRemoteList', 'Lists', 'taoBackOffice');
            const delEltUrl  = urlUtil.route('removeListElement', 'Lists', 'taoBackOffice');

            if ($('section[data-new-list]').length > 0) {
                const id = $('section[data-new-list]').first().attr('id');
                const uriList = id.replace('list-data_', '');
                handleEditList(uriList);
            }

            $('.list-edit-btn').click(handleEditList);

            $('.list-delete-btn').click(function () {
                const $btn  = $(this);
                dialogConfirm(
                    __('Please confirm you want to delete this list. This operation cannot be undone.'),
                    function accept() {
                        const uri   = $btn.data('uri');
                        const $list = $btn.parents('.data-container');
                        $.postJson(
                            delListUrl,
                            { uri },
                            response => {
                                if (response.deleted) {
                                    feedback().success(__('List deleted'));
                                    $list.remove();
                                } else {
                                    feedback().error(__('List not deleted'));
                                }
                            }
                        );
                    }
                );
            });

            $('.list-reload-btn').click(function () {
                const uri = $(this).data('uri');

                $.postJson(
                    reloadListUrl,
                    { uri },
                    response => {
                        if (response.saved) {
                            feedback().success(__('List reloaded'));
                            section.get('taoBo_remotelist').loadContentBlock(urlUtil.route('remote', 'Lists', 'taoBackOffice'));
                        } else {
                            feedback().error(__('List failed to be reloaded'));
                        }
                    }
                );
            });
        }
    };
});

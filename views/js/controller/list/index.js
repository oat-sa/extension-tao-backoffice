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
    'util/url',
    'ui/feedback',
    'ui/dialog/confirm',
    'layout/section',
    'css!taoBackOfficeCss/list'
], function ($, __, urlUtil, feedback, dialogConfirm, section) {
    'use strict';

    function addSquareBtn(title, icon, $listToolBar, position='rgt') {
        const $btn = $('<button>', {
            'class': `btn-info small ${position} icon-${icon}`,
            title: __(title) }
        );
        $listToolBar.append($btn);
        return $btn;
    }

    return {

        /**
         * The list controller entrypoint
         */
        start() {

            const saveUrl    = urlUtil.route('saveLists', 'Lists', 'taoBackOffice');
            const delListUrl = urlUtil.route('removeList', 'Lists', 'taoBackOffice');
            const delEltUrl  = urlUtil.route('removeListElement', 'Lists', 'taoBackOffice');

            $('.list-edit-btn').click(function () {
                const $btn = $(this);
                let uri = $btn.data('uri');
                const $listContainer = $(`#list-data_${uri}`);

                let $listForm       = $listContainer.find('form');
                const $listTitleBar = $listContainer.find('.container-title h6');
                const $listToolBar  = $listContainer.find('.data-container-footer').empty();
                let $listSaveBtn;
                let $listNewBtn;

                if (!$listForm.length) {

                    $listForm = $('<form>');
                    $listContainer.wrapInner($listForm);
                    $listContainer.find('form').append(`<input type='hidden' name='uri' value='${uri}' />`);

                    const $labelEdit = $(`<input type='text' name='label' value=''/>`).val($listTitleBar.text());
                    $listTitleBar.closest('.container-title').html($labelEdit);

                    if ($listContainer.find('.list-element').length) {
                        $listContainer.find('.list-element').replaceWith(function () {
                            return $(`<input type='text' name='${$(this).attr('id')}' value='' />`).val($(this).text());
                        });
                    }

                    const elementList = $listContainer.find('ol');
                    elementList.addClass('sortable-list');
                    elementList.find('li')
                        .addClass('list-element')
                        .append(`<span class='icon-checkbox-crossed list-element-delete-btn'></span>`)
                        .wrapInner(`<div class='sortable-list__element'>`);

                    $listSaveBtn = addSquareBtn(__('Save list'), 'save', $listToolBar);
                    $listSaveBtn.on('click', function () {
                        $.postJson(
                            saveUrl,
                            $(this).closest('form').serializeArray(),
                            response => {
                                if (response.saved) {
                                    feedback().success(__('List saved'));
                                    section.get('taoBo_list').loadContentBlock(urlUtil.route('index', 'Lists', 'taoBackOffice'));
                                }else{
                                    feedback().error(__('List not saved'));
                                }
                            }
                        );
                        return false;
                    });

                    $listNewBtn = addSquareBtn('New element', 'add', $listToolBar);
                    $listNewBtn.click(function () {
                        const $list = $(this).closest('form').find('ol');
                        const level = $list.children().length + 1;

                        $list
                            .append(`<li id='list-element_${level}' class='list-element'>
                                <div class='sortable-list__element'>
                                    <input type='text' name='list-element_${level}_' />
                                    <span class='icon-checkbox-crossed list-element-delete-btn' ></span>
                                </div>
                            </li>`);
                        $list.closest('.container-content').scrollTop($list.height());

                        return false;
                    });
                }

                $listContainer.on('click', '.list-element-delete-btn', function () {
                    const $element = $(this).closest('.list-element');
                    const $input   = $element.find('input:text');
                    const eltUri   = $input.attr('name').replace(/^list-element_([0-9]*)_/, '');

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
            });

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
        }
    };
});

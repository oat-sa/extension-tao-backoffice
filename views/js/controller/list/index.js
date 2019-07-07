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
    'layout/section',
    'css!taoBackOfficeCss/list'
], function ($, __, urlUtil, feedback, section) {
    'use strict';

    function _addSquareBtn(title, icon, $listToolBar) {
        const $btn = $('<button>', {
            'class': `btn-info small lft icon-${icon}`,
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

                // form must be on the inside rather than on the outside as it has been in 2.6
                let $listForm       = $listContainer.find('form');
                const $listTitleBar = $listContainer.find('.container-title h6');
                const $listToolBar  = $listContainer.find('.data-container-footer');
                let $listSaveBtn;
                let $listNewBtn;

                if (!$listForm.length) {

                    $listForm = $('<form>');
                    $listContainer.wrapInner($listForm);
                    $listContainer.find('form').append(`<input type='hidden' name='uri' value='${uri}' />`);

                    const $labelEdit = $(`<input type='text' name='label' value=''/>`).val($listTitleBar.text());
                    $listTitleBar.html($labelEdit);

                    if ($listContainer.find('.list-element').length) {
                        $listContainer.find('.list-element').replaceWith(function () {
                            return $(`<input type='text' name='${$(this).attr('id')}' value='' />`).val($(this).text());
                        });
                    }

                    const elementList = $listContainer.find('ol');
                    elementList.addClass('sortable-list');
                    elementList.find('li').append(`<span class='icon-checkbox-crossed list-element-delete-btn'></span>`);

                    $listSaveBtn = _addSquareBtn(__('Save element'), 'save', $listToolBar);
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

                    $listNewBtn = _addSquareBtn('New element', 'add', $listToolBar);
                    $listNewBtn.click(function () {
                        var level = $(this).closest('form').find('ol').children().length + 1;
                        $(this).closest('form')
                            .find('ol')
                            .append(`<li id='list-element_${level}'>
                                <input type='text' name='list-element_${level}_' />
                                <span class='icon-checkbox-crossed list-element-delete-btn' ></span>
                            </li>`);
                        return false;
                    });
                }

                $listContainer.on('click', '.list-element-delete-btn', function () {
                    const $element = $(this).parent();
                    const $input   = $element.find('input:text');

                    if ($input.val() === '' || window.confirm(__('Please confirm you want to delete this list element.'))) {
                        let eltUri = $input.attr('name').replace(/^list\-element\_([1-9]*)\_/, '');
                        if (eltUri) {
                            $.postJson(
                                delEltUrl,
                                { uri : eltUri },
                                response => {
                                    if (response.deleted) {
                                        $element.remove();
                                        feedback().success(__('Element deleted'));
                                    }else{
                                        feedback().error(__('Element not deleted'));
                                    }
                                }
                            );
                        } else {
                            $element.remove();
                            feedback().success(__('Element deleted'));
                        }
                    }
                });
            });

            $('.list-delete-btn').click(function () {
                if (window.confirm(__('Please confirm you want to delete this list. This operation cannot be undone.'))) {
                    const $btn  = $(this);
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
            });
        }
    };
});

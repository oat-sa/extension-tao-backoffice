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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

/**
 * Tree view controller
 */
define([
    'jquery',
    'lodash',
    'layout/actions/binder',
    'uri',
    'context',
    'helpers',
    'taoBackOffice/ui/tree',
    'layout/section'
], function ($, _ , binder, uri, context, helpers, treeFactory, section) {
    'use strict';

    /**
     * Open a resource in a new section
     * @param {String} uri - the resource URI
     * @param {String} [name] - the name to display on the section
     */
    function openResource(uri, name){
        name = name || uri;
        var url = helpers._url('editInstance', 'Trees', 'taoBackOffice', {'uri': uri });
        section.create({
            id           : 'node-' + encodeURIComponent(uri),
            name         : name,
            url          : url,
            contentBlock : true
        })
        .show();
    }

    /**
     * Render the trees
     * @param {jQueryElement} $treeContainer - container with a data-id attr that contains the tree
     */
    function renderTrees ($treeContainer){
        var trees = [];
        var getTreeUrl = helpers._url('getTree', 'Trees', 'taoBackOffice');

        $treeContainer.each( function() {
            var $container = $(this);
            var uri = $container.data('id');

            //get tree data
            $.post(getTreeUrl, {uri: uri}, function (treeData) {

                var tree = treeFactory();
                tree
                    .render($container, treeData)
                    .onClick(function (e){

                        var node = _.find(treeData.nodes, { id : e.nodes[0] });
                        if(node){
                            openResource(node.id, node.label);
                        }
                    });
                trees.push(tree);
            });
        });

        //refresh the current tree
        section.current().on('show', function(){
            if(section.id === 'taoBo_tree' && trees.length){
                _.invoke(trees, 'refresh');
            }
        });
    }


    /**
     * The Controller
     */
    var treeViewController = {

        /**
         * Controller entry point
         */
        start: function start () {

            //render the trees
            renderTrees($('.tree-container'));
        }
    };

    return treeViewController;
});

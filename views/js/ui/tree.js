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
 */

/**
 * Hierarchical tree component
 */
define([
    'jquery',
    'lodash',
    'taoBackOffice/lib/vis/vis.min',
    'css!taoBackOffice/lib/vis/vis.min'
], function($, _, vis) {
    'use strict';

    /**
     * Tree default options based on {@link http://visjs.org/docs/network/#options}
     */
    var defaultOptions = {
        width: '100%',
        height: '100%',
        layout: {
            hierarchical: {
                sortMethod: 'directed',
                "levelSeparation": 200
            }
        },
        nodes: {
            shape: 'box',
            labelHighlightBold: false,
            color: {
                border: "#222",
                background: "#f2f0ee",
                highlight: {
                    border: "#0E5D91",
                },
                hover: {
                    border: "#0E5D91",
                }
            },
            font: {
                face: "'Franklin Gothic', 'Franklin Gothic Medium', 'Source Sans Pro', sans-serif",
                color: "#222",
            }
        },
        edges: {
            smooth: false,
            width: 0.2,
            color: {
                color: "#222"
            },
            arrows: {
                to: true
            },
            physics: false,
            scaling: {
                label: false
            }
        },
        interaction: {
            hover: true
        }
    };


    /**
     * Create a new tree component
     * @param {Object} [options] - see {@link http://visjs.org/docs/network/#options}
     * @returns {tree} the tree component
     */
    var treeFactory = function treeFactory(options) {

        var network;

        options = _.defaults(options || {}, defaultOptions);

        /**
         * @typedef tree
         */
        var tree = {

            /**
             * Renders the tree
             * @param {jQueryElement|HTMLElement} container - where to render the tree
             * @param {Object} data - the tree data
             * @param {Object[]} nodes - nodes with at least an id and a label
             * @param {Object[]} edges - edges definition
             * @returns {tree} for chaining
             */
            render: function render(container, data) {

                //check container
                if (container instanceof $ && container.length) {
                    container = container[0]; //get 1st element if it's a jquery selection
                }
                if (!(container instanceof HTMLElement)) {
                    throw new TypeError('The tree container should be an html element or a jquery element');
                }
                if(!_.isPlainObject(data)){
                    throw new TypeError('The tree needs data with nodes and/or edges');
                }


                //ensure data format
                data = {
                    nodes: new vis.DataSet(data.nodes || []),
                    edges: new vis.DataSet(data.edges || [])
                };

                network = new vis.Network(container, data, options);
                network.on('hoverNode', function() {
                    container.style.cursor = 'pointer';
                });
                network.on('hoverNode', function() {
                    container.style.cursor = 'pointer';
                });
                network.on('blurNode', function() {
                    container.style.cursor = 'default';
                });
                return this;
            },

            /**
             * Bind a click event on nodes
             * @param {Function} cb - click handler
             * @returns {tree} for chaining
             */
            onClick: function onClick(cb) {
                if (network && _.isFunction(cb)) {
                    network.on('click', cb);
                }
                return this;
            },

            /**
             * Refresh the tree
             * @returns {tree} for chaining
             */
            refresh : function refresh(){
                if(network){
                    //FIXME do not seem to reposition
                    network.redraw();
                }
                return this;
            },

            /**
             * Destroy the current tree
             * @returns {tree} for chaining
             */
            destroy: function destroy() {
                if (network) {
                    network.destroy();
                }
                return this;
            }
        };
        return tree;
    };

    return treeFactory;
});


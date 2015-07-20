define([
    'jquery',
    'taoBackOffice/lib/vis',
    'css!taoBackOfficeCss/vis'
], function ($, vis) {
    'use strict';

    var network = null;
    var treeContainer = null;
    var settings = null;
    var data = null;

    /**
     * @private
     */
    function destroy() {
        if (network !== null) {
            network.destroy();
            network = null;
        }
    }

    var treeRender = {

        /**
         *
         * @param {Element} container
         * @param {Object} [treeData]
         * @param {Object} [options]
         */
        init: function (container, treeData, options) {

            if (!container instanceof Element) {
                throw Error('tree container must be specified');
            }

            treeContainer = container;
            options = options || {};
            treeData = treeData || {nodes: [], edges: []};

            // create a network
            data = {
                nodes: treeData.nodes ? treeData.nodes : [],
                edges: treeData.edges ? treeData.edges : []
            };

            settings = {
                layout: {
                    hierarchical: {
                        sortMethod: 'directed'
                    }
                },
                nodes: {
                    shape: 'box',
                    "color": {
                        "border": "#266d9c",
                        "background": "rgba(255,247,246,1)",

                        "highlight": {
                            "border": "#266d9c",
                            "background": "rgba(255,247,246,1)"
                        }
                    },

                    "font": {
                        "face": "Franklin Gothic",
                        "color": "#266d9c"
                    }

                },
                edges: {
                    smooth: false,
                    arrows: {to: true},
                    "physics": false,
                    "scaling": {
                        "label": false
                    }
                }
            };

            $.extend(settings, options);

        },

        run: function () {
            destroy();

            network = new vis.Network(treeContainer, data, settings);

        }
    };

    return treeRender;
});
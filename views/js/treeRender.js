define([
    'jquery',
    'lodash',
    'taoBackOffice/lib/vis/vis.min',
    'css!taoBackOffice/lib/vis/vis.min'
], function ($, _, vis) {
    'use strict';

    var storage = [];

    /**
     * @private
     */
    function removeTree(id) {
        if (!_.isUndefined(storage[id].network) && storage[id].network !== null) {
            storage[id].network.removeTree();
            storage[id].network = null;
        }
    }

    /**
     *
     * @param {Element} container
     * @param {Object} [treeData]
     * @param {Object} [options]
     */
    function init (container, treeData, options) {

        if (!container instanceof Element) {
            throw new TypeError("tree container must be specified");
        }
        var uid = _.uniqueId();

        storage[uid] = {};

        options = options || {};
        treeData = treeData || {nodes: [], edges: []};

        var settings = {
            layout: {
                hierarchical: {
                    sortMethod: 'directed',
                    "levelSeparation": 200
                }
            },
            nodes: {
                shape: 'box',
                "color": {
                    "border": "#222",
                    "background": "#f2f0ee",

                    "highlight": {
                        "border": "#222",
                        "background": "#f2f0ee"
                    }
                },

                "font": {
                    "face": "'Franklin Gothic', 'Franklin Gothic Medium', 'Source Sans Pro', sans-serif",
                    "color": "#222"
                }

            },
            edges: {
                smooth: false,
                width: 0.2,
                color: {
                    color: "#222"
                },
                arrows: {to: true},
                "physics": false,
                "scaling": {
                    "label": false
                }
            }
        };

        storage[uid].settings = $.extend(settings, options);


        storage[uid].data = {
            nodes: treeData.nodes ? treeData.nodes : [],
            edges: treeData.edges ? treeData.edges : []
        };

        return uid;

    }

    var treeRender = {


        run: function (container, treeData, options) {
            var uid = init(container, treeData, options);
            removeTree(uid);

            var network = new vis.Network(container, storage[uid].data, storage[uid].settings);

            network.once('initRedraw', function () {

                if (storage[uid].data.nodes.length > 100) {
                    network.setOptions($.extend(settings, {
                        physics: {
                            hierarchicalRepulsion: {
                                nodeDistance: 200
                            },
                            stabilization: {
                                fit: false
                            }
                        }
                    }));

                    network.fit({
                        nodes: [storage[uid].data.nodes[0].id, storage[uid].data.nodes[1].id], animation: {
                            duration: 400,
                            easingFunction: 'linear'
                        }
                    });
                }

            });
            storage[uid].network  = network;
            return network;
        },


        destroy: function (id) {
            removeTree(id);
            if (!_.isUndefined(storage[id])) {
                storage[id] = {};
            }
    }


    };

    return treeRender;
});
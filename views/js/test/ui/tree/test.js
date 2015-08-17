define([
    'jquery',
    'taoBackOffice/ui/tree'
], function($, treeFactory) {
    'use strict';

    //mock data
    var treeData = {"nodes":[{"id":"http:\/\/bertao\/tao.rdf#i1439560332811653","label":"Milky corp"},{"id":"http:\/\/bertao\/tao.rdf#i143956033216354","label":"State of moos"},{"id":"http:\/\/bertao\/tao.rdf#i1439560332646655","label":"State of cows"},{"id":"http:\/\/bertao\/tao.rdf#i1439560332184156","label":"Meat University"},{"id":"http:\/\/bertao\/tao.rdf#i1439560332980957","label":"University of South Meat"},{"id":"http:\/\/bertao\/tao.rdf#i1439560332352358","label":"New College of Cows"},{"id":"http:\/\/bertao\/tao.rdf#i1439560332818759","label":"University of Milk"}],"edges":[{"from":"http:\/\/bertao\/tao.rdf#i1439560332811653","to":"http:\/\/bertao\/tao.rdf#i143956033216354"},{"from":"http:\/\/bertao\/tao.rdf#i1439560332811653","to":"http:\/\/bertao\/tao.rdf#i1439560332646655"},{"from":"http:\/\/bertao\/tao.rdf#i143956033216354","to":"http:\/\/bertao\/tao.rdf#i1439560332184156"},{"from":"http:\/\/bertao\/tao.rdf#i143956033216354","to":"http:\/\/bertao\/tao.rdf#i1439560332980957"},{"from":"http:\/\/bertao\/tao.rdf#i143956033216354","to":"http:\/\/bertao\/tao.rdf#i1439560332352358"},{"from":"http:\/\/bertao\/tao.rdf#i1439560332646655","to":"http:\/\/bertao\/tao.rdf#i1439560332818759"}]};

    QUnit.module('API');

    QUnit.test('module', 2, function(assert) {
        assert.ok(typeof treeFactory !== 'undefined', "The module exposes something");
        assert.ok(typeof treeFactory === 'function', "The module exposes a function");
    });

    QUnit.test('factory', 3, function(assert) {
        assert.ok(typeof treeFactory === 'function', "The factory is a function");
        assert.ok(typeof treeFactory() === 'object', "The factory creates an object");
        assert.notEqual(treeFactory(), treeFactory(), "The factory creates an new object");
    });

    QUnit.test('tree', 5, function(assert) {
        var tree = treeFactory();
        assert.ok(typeof tree === 'object', "The tree is an object");
        assert.ok(typeof tree.render === 'function', "The tree has a render method");
        assert.ok(typeof tree.onClick === 'function', "The tree has an onclick method");
        assert.ok(typeof tree.refresh === 'function', "The tree has a refresh method");
        assert.ok(typeof tree.destroy === 'function', "The tree has a destroy method");
    });

    QUnit.module('Rendering');

    QUnit.test('initilization', 4, function(assert) {
        var tree = treeFactory();

        assert.throws(function(){
            tree.render();
        }, TypeError, 'Render needs a container');


        assert.throws(function(){
            tree.render('foo');
        }, TypeError, 'Render needs a valid container');

        assert.throws(function(){
            tree.render($('foo'));
        }, TypeError, 'Render needs an existing container');

        assert.throws(function(){
            tree.render(document.querySelector('.container'));
        }, TypeError, 'Render needs data');
    });

    QUnit.asyncTest('DOM', 5, function(assert) {

        var $container = $('.container');

        var tree = treeFactory();

        assert.equal($container.children().length, 0, 'The container is empty');
        assert.equal(tree.render($container, treeData), tree, "The tree render chains");

        setTimeout(function(){
            assert.equal($container.children().length, 1, 'The tree as created a node in the container');
            assert.equal($('canvas', $container).length, 1, 'The tree as created a canvas inside the container');

            tree.destroy();
            assert.equal($container.children().length, 0, 'The container is now empty');

            QUnit.start();
        }, 100);

    });

    QUnit.module('Visual test');

    QUnit.asyncTest('render a tree', 1, function(assert) {

        var container = document.querySelector('.outside-container');

        treeFactory().render(container, treeData);

        setTimeout(function(){
            assert.equal(container.querySelectorAll('canvas').length, 1, 'The canvas is created');

            QUnit.start();
        }, 100);
    });

});


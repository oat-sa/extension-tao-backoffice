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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA ;
 */

import urlBO from '../utils/urls';
import selectorsBO from '../utils/selectors';
import { getRandomNumber } from '../../../../tao/views/cypress/utils/helpers';


const LIST_NAME_PREFIX = 'Test E2E list';

/**
 * Remove entries that was created by test case
 */
const clearData = () => {
    cy.log('Clear data');
    cy.getSettled(selectorsBO.lists)
        .each($list => {
            if ($list.find(selectorsBO.listName).text().includes(LIST_NAME_PREFIX)) {
                const uri = $list.find(selectorsBO.listDeleteButton).attr('data-uri').split('_').pop();
                deleteList(uri);
            }
        });
};

const createList = () => {
    cy.intercept('POST', urlBO.list.index).as('createList');
    cy.getSettled(selectorsBO.createListButton)
        .should('have.text', ' Create list')
        .should('be.visible')
        .click();

    return cy.wait('@createList');
};

const confirmModal = () => {
    cy.getSettled('[data-control="navigable-modal-body"]')
        .find('button[data-control="ok"]')
        .should('be.visible')
        .click();
};

const saveList = (uri = false) => {
    let targetSelector = uri ? (`[id$="${uri}"]`) : selectorsBO.listLast;

    cy.getSettled(targetSelector)
        .find(selectorsBO.listNameInput)
        .clear()
        .type(`${LIST_NAME_PREFIX}_${getRandomNumber()}`);

    cy.intercept('POST', urlBO.list.save).as('saveList');
    cy.getSettled(targetSelector)
        .find(selectorsBO.saveElementButton)
        .should('be.visible')
        .click();

    return cy.wait('@saveList');
};

const deleteList = (uri = false) => {
    let targetSelector = uri ? (`[id$="${uri}"]`) : selectorsBO.listLast;

    cy.log(`Deleting list: ${targetSelector}`);

    cy.getSettled(targetSelector)
        .find(selectorsBO.listDeleteButton)
        .scrollIntoView()
        .should('be.visible')
        .click();
    cy.intercept('POST', '**/taoBackOffice/Lists/removeList').as('removeList');
    confirmModal();
    cy.wait('@removeList');
};

describe('Managing lists', () => {
    before(() => {
        cy.loginAsAdmin();
        cy.intercept('GET', urlBO.list.index).as('getLists')
        cy.visit(urlBO.settingsList);
        cy.wait('@getLists');

        clearData();
    });

    after(()=>{
        clearData();
    });

    afterEach(()=>{
        cy.intercept('GET', urlBO.list.index).as('getLists')
        cy.visit(urlBO.settingsList);
        cy.wait('@getLists');
    });

    it('List creating', () => {
        let listsTotal;
        // Check state before creating a new list
        cy.get(selectorsBO.lists)
            .then(list => {
                listsTotal = Cypress.$(list).length;
                expect(list).to.have.length(listsTotal);
            });

        createList()
            .then((interception) => {
                // Validate response
                assert.isNotNull(interception.response.body.data.label, 'Response has label');
                assert.isNotNull(interception.response.body.data.uri, 'Response has URI');
                assert.isNotNull(interception.response.body.data.elements, 'Response has Elements');

                cy.getSettled(selectorsBO.listLast)
                    .find('input[id^="https_"]')
                    .scrollIntoView()
                    .check();

                cy.getSettled(selectorsBO.listLast)
                    .find(selectorsBO.listNameInput)
                    .should('have.value', interception.response.body.data.label);

                cy.getSettled(selectorsBO.listLast)
                    .find(selectorsBO.elementNameInput)
                    .should('have.value', interception.response.body.data.elements[0].label);

                cy.getSettled(selectorsBO.listLast)
                    .find(selectorsBO.elementUriInput)
                    .should('have.value', interception.response.body.data.elements[0].uri);
            });

        saveList();

        // Validate +1 list
        cy.get(selectorsBO.lists)
            .then(listing => {
                expect(listing).to.have.length(listsTotal + 1);
            });
    });

    it('List editing', () => {
        createList()
            .then((interception)=>{
                const uri = interception.response.body.data.uri.split('#').pop();
                const number = getRandomNumber();
                const listName = `${LIST_NAME_PREFIX}_${number}`;
                const elementAddName = `New name is ${number}`;
                const elementAddURI = `New uri is ${number}`;
                const elementRename = `Renamed is ${number}`;
                const elementRenameURI = `Renamed uri is ${number}`;

                saveList(uri);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.listEditButton)
                    .scrollIntoView()
                    .should('be.visible')
                    .click();

                // Edit list
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.listNameInput)
                    .clear()
                    .type(listName);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.editUriCheckbox)
                    .check();

                // Rename element
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.elementsList)
                    .find('li:last-child')
                    .find(selectorsBO.elementNameInput)
                    .should('be.visible')
                    .type(elementAddName);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.elementsList)
                    .find('li:last-child')
                    .find(selectorsBO.elementUriInput)
                    .should('be.visible')
                    .type(elementAddURI);

                // Add element
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.addElementButton)
                    .should('be.visible')
                    .click();

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.elementsList)
                    .find('li:last-child')
                    .find(selectorsBO.elementNameInput)
                    .should('be.visible')
                    .type(elementRename);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.elementsList)
                    .find('li:last-child')
                    .find(selectorsBO.elementUriInput)
                    .should('be.visible')
                    .type(elementRenameURI);

                // Save list
                cy.intercept('POST', urlBO.list.save).as('saveList');
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.saveElementButton)
                    .should('be.visible')
                    .click();
                cy.wait('@saveList');

                // Validate after saving
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.listName)
                    .should('have.text', listName);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.elementsList)
                    .children()
                    .should('have.length', 2);

                cy.getSettled(`section[id$="${uri}"]`)
                    .children()
                    .contains(elementAddName)
                    .should('have.length', 1);

                cy.getSettled(`section[id$="${uri}"]`)
                    .children()
                    .contains(elementRename)
                    .should('have.length', 1);
            });
    });

    it('Elements removing', () => {
        createList()
            .then((interception)=>{
                const uri = interception.response.body.data.uri.split('#').pop();
                saveList(uri);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.listEditButton)
                    .scrollIntoView()
                    .should('be.visible')
                    .click();

                // Edit list
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.deleteElementButton)
                    .should('be.visible')
                    .click();

                confirmModal();

                cy.intercept('POST', urlBO.list.save).as('saveList');
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.saveElementButton)
                    .should('be.visible')
                    .click();
                cy.wait('@saveList');

                // Validate after saving
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.elementsList)
                    .children()
                    .should('have.length', 0);
            });
    });

    it('List deletion', () => {
        createList()
            .then((interception)=>{
                const uri = interception.response.body.data.uri.split('#').pop();
                let listsTotal;

                saveList(uri);

                cy.get(selectorsBO.lists)
                    .then(listing => {
                        listsTotal = Cypress.$(listing).length;
                        expect(listing).to.have.length(listsTotal);
                    });

                cy.intercept('POST', urlBO.list.remove).as('deleteList');

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsBO.listDeleteButton)
                    .scrollIntoView()
                    .should('be.visible')
                    .click();

                confirmModal();

                cy.wait('@deleteList');

                cy.get(selectorsBO.lists)
                    .then(listing => {
                        expect(listing).to.have.length(listsTotal - 1);
                    });
            });
    });
});

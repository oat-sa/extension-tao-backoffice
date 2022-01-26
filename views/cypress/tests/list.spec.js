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

import urls from '../../../../tao/views/cypress/utils/urls';
import selectorsList from '../../../../tao/views/cypress/utils/selectors/list';
import { getRandomNumber } from '../../../../tao/views/cypress/utils/helpers';


const LIST_NAME_PREFIX = 'Test E2E list';

/**
 * Remove entries that was created by test case
 */
const clearData = () => {
    cy.log('Clear data');
    cy.getSettled(selectorsList.lists)
        .each($list => {
            if ($list.find(selectorsList.listName).text().includes(LIST_NAME_PREFIX)) {
                const uri = $list.find(selectorsList.listDeleteButton).attr('data-uri').split('_').pop();
                cy.deleteList(uri);
            }
        });
};


describe('Managing lists', () => {
    before(() => {
        cy.loginAsAdmin();
        cy.intercept('GET', urls.list.index).as('getLists')
        cy.visit(urls.settings.list);
        cy.wait('@getLists');

        clearData();
    });

    after(()=>{
        clearData();
    });

    afterEach(()=>{
        cy.intercept('GET', urls.list.index).as('getLists')
        cy.visit(urls.settings.list);
        cy.wait('@getLists');
    });

    it('List creating', () => {
        let listsTotal;

        // Check state before creating a new list
        cy.get(selectorsList.lists)
            .then(list => {
                listsTotal = Cypress.$(list).length;
                expect(list).to.have.length(listsTotal);
            });

        cy.createList()
            .then((interception) => {
                // Validate response
                assert.isNotNull(interception.response.body.data.label, 'Response has label');
                assert.isNotNull(interception.response.body.data.uri, 'Response has URI');
                assert.isNotNull(interception.response.body.data.elements, 'Response has Elements');

                cy.getSettled(selectorsList.listLast)
                    .find('input[id^="https_"]')
                    .scrollIntoView()
                    .check();

                cy.getSettled(selectorsList.listLast)
                    .find(selectorsList.listNameInput)
                    .should('have.value', interception.response.body.data.label);

                cy.getSettled(selectorsList.listLast)
                    .find(selectorsList.elementNameInput)
                    .should('have.value', interception.response.body.data.elements[0].label);

                cy.getSettled(selectorsList.listLast)
                    .find(selectorsList.elementUriInput)
                    .should('have.value', interception.response.body.data.elements[0].uri);
            });

        cy.saveList(`${LIST_NAME_PREFIX}_${getRandomNumber()}`);

        // Validate +1 list
        cy.get(selectorsList.lists)
            .then(listing => {
                expect(listing).to.have.length(listsTotal + 1);
            });
    });

    it('List editing', () => {
        cy.createList()
            .then((interception)=>{
                const uri = interception.response.body.data.uri.split('#').pop();
                const number = getRandomNumber();
                const listName = `${LIST_NAME_PREFIX}_${number}`;
                const elementRename = `Updated name is ${number}`;
                const elementsToAdd = 1; // TODO: after BE fix increase it to enable multiple add
                let elementsNames = [];

                cy.saveList(`${LIST_NAME_PREFIX}_${getRandomNumber()}`, uri);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.listEditButton)
                    .scrollIntoView()
                    .should('be.visible')
                    .click();

                // Edit list
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.listNameInput)
                    .clear()
                    .type(listName);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.editUriCheckbox)
                    .check();

                // Rename element
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.elementsList)
                    .find('li:last-child')
                    .find(selectorsList.elementNameInput)
                    .should('be.visible')
                    .clear()
                    .type(elementRename);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.elementsList)
                    .find('li:last-child')
                    .find(selectorsList.elementUriInput)
                    .should('be.visible')
                    .clear()
                    .type(`Updated uri is ${number}`);

                // Add elements
                for(let i = 0; i < elementsToAdd; i++) {
                    let elementName = `New name is ${getRandomNumber()}`;
                        cy.getSettled(`section[id$="${uri}"]`)
                            .find(selectorsList.addElementButton)
                            .should('be.visible')
                            .click();

                        cy.getSettled(`section[id$="${uri}"]`)
                            .find(selectorsList.elementsList)
                            .find('li:last-child')
                            .find(selectorsList.elementNameInput)
                            .should('be.visible')
                            .type(elementName);

                        cy.getSettled(`section[id$="${uri}"]`)
                            .find(selectorsList.elementsList)
                            .find('li:last-child')
                            .find(selectorsList.elementUriInput)
                            .should('be.visible')
                            .type(`new uri is ${getRandomNumber()}`);

                        elementsNames.push(elementName);
                }

                // Save
                cy.intercept('POST', urls.list.save).as('saveList');
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.saveElementButton)
                    .should('be.visible')
                    .click();
                cy.wait('@saveList');

                // Validate after saving
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.listName)
                    .should('have.text', listName);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.elementsList)
                    .children()
                    .should('have.length', (elementsToAdd + 1));

                    cy.getSettled(`section[id$="${uri}"]`)
                    .children()
                    .contains(elementRename)
                    .should('have.length', 1);

                elementsNames.forEach((name) => {
                    cy.getSettled(`section[id$="${uri}"]`)
                        .children()
                        .contains(name)
                        .should('have.length', 1);
                    });
            });
    });

    it('Elements removing', () => {
        cy.createList()
            .then((interception)=>{
                const uri = interception.response.body.data.uri.split('#').pop();
                cy.saveList(`${LIST_NAME_PREFIX}_${getRandomNumber()}`, uri);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.listEditButton)
                    .scrollIntoView()
                    .should('be.visible')
                    .click();

                // Edit list
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.deleteElementButton)
                    .should('be.visible')
                    .click();

                cy.modalConfirm();

                cy.intercept('POST', urls.list.save).as('saveList');
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.saveElementButton)
                    .should('be.visible')
                    .click();
                cy.wait('@saveList');

                // Validate after saving
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.elementsList)
                    .children()
                    .should('have.length', 0);
            });
    });

    it.skip('Disable "Add elements" button when maximum elements is reached', () => {
        const limit = 5;

        // Mock limit to 5
        cy.getSettled(selectorsList.maxItems)
            .then(($input) => {
                assert.isAbove(parseInt($input.val()), 0, 'Value more than 0');
                $input.val(limit);
            });

        cy.createList()
            .then((interception)=>{
                const uri = interception.response.body.data.uri.split('#').pop();

                // Add elements
                for(let i = 0; i < limit + 1; i++) {
                    cy.getSettled(`section[id$="${uri}"]`)
                        .find(selectorsList.addElementButton)
                        .should('be.visible')
                        .click();

                    cy.getSettled(`section[id$="${uri}"]`)
                        .find(selectorsList.elementsList)
                        .find('li:last-child')
                        .find(selectorsList.elementNameInput)
                        .should('be.visible')
                        .type(`New name is ${number}`);
                }

                // Validate disabled state
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.elementsList)
                    .children()
                    .should('have.length', limit);

                cy.getSettled(`section[id$="${uri}"] `)
                    .find(selectorsList.addElementButton)
                    .should('be.visible')
                    .should('be.disabled');

                // Remove element to trigger an enabled state
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.elementsList)
                    .find('li:last-child')
                    .find(selectorsList.deleteElementButton)
                    .should('be.visible')
                    .click();
                cy.modalConfirm();

                // Validate enabled state
                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.elementsList)
                    .children()
                    .should('have.length', limit - 1);

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.addElementButton)
                    .should('be.visible')
                    .should('not.be.disabled');

                cy.saveList(`${LIST_NAME_PREFIX}_${getRandomNumber()}`, uri);
            });
    });

    it('List deletion', () => {
        cy.createList()
            .then((interception)=>{
                const uri = interception.response.body.data.uri.split('#').pop();
                let listsTotal;

                cy.saveList(`${LIST_NAME_PREFIX}_${getRandomNumber()}`, uri);

                cy.get(selectorsList.lists)
                    .then(listing => {
                        listsTotal = Cypress.$(listing).length;
                        expect(listing).to.have.length(listsTotal);
                    });

                cy.intercept('POST', urls.list.remove).as('deleteList');

                cy.getSettled(`section[id$="${uri}"]`)
                    .find(selectorsList.listDeleteButton)
                    .scrollIntoView()
                    .should('be.visible')
                    .click();

                cy.modalConfirm();

                cy.wait('@deleteList');

                cy.get(selectorsList.lists)
                    .then(listing => {
                        expect(listing).to.have.length(listsTotal - 1);
                    });
            });
    });
});

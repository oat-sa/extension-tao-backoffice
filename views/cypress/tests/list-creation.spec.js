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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA ;
 */

import boURL from '../utils/urls';
import boSelectors from '../utils/selectors';


describe('Item Authoring', () => {
    describe('Managing lists page', () => {
        it('Navigate to the list page via settings', function () {
            cy.loginAsAdmin();

            cy.intercept('GET', '**/index?structure=settings*')
                .as('settings')
                .visit(boURL.settingsList)
                .wait('@settings');

            cy.url().should('include', 'section=settings_ext_mng');
            cy.get(boSelectors.listButton)
                .should('have.text', 'Lists')
                .should('be.visible')
                .click();
            cy.url().should('include', 'section=taoBo_list');
        });

        describe('Creation, editing and deleting list', () => {
            let createdLists = [];

            before(() => {
                // TODO: Cleanup createdLists (if any)
                // Create one list
            });
            after(() => {
                // Delete created list
            });

            it.only('Create new lists', function () {
                let listName;
                let listsTotal;

                cy.loginAsAdmin();
                cy.intercept('GET', '**/taoBackOffice/Lists/index').as('getLists')
                cy.visit(boURL.settingsList);
                cy.wait('@getLists');
                cy.url().should('include', 'section=taoBo_list');

                cy.get(boSelectors.listContainer)
                    .then(listing => {
                        listsTotal = Cypress.$(listing).length;
                        expect(listing).to.have.length(listsTotal);
                    });

                cy.intercept('POST', '**/taoBackOffice/Lists/index').as('createList');

                cy.getSettled(boSelectors.createListButton)
                    .should('have.text', ' Create list')
                    .should('be.visible')
                    .click();
                // OR Using submit form
                // cy.get('#createList')
                //     .should('be.visible')
                //     .submit();


                cy.wait('@createList');
                // TODO: Read listName from response (data.label)

                cy.get(boSelectors.listContainer)
                    .then(listing => {
                        expect(listing).to.have.length(listsTotal + 1);
                    });

                // TODO: Check inputs are visible, has set values, name === listName
            });

            it('Edit lists', function () {
                let listsTotal;

                cy.loginAsAdmin();
                cy.visit(boURL.settingsList);
                cy.url().should('include', 'section=taoBo_list');

                cy.get(boSelectors.listContainer)
                    .then(listing => {
                        listsTotal = Cypress.$(listing).length;
                        expect(listing).to.have.length(listsTotal);
                    });

                cy.intercept('GET', '**/taoBackOffice/Lists/getListElements/*').as('getElements');

                cy.getSettled(boSelectors.editListButton)
                    .should('be.visible')
                    .click();
                cy.wait('@createList');

                cy.get('.data-container form .container-title text').should('be.visible');

                cy.wait('@getElements');

                cy.get(boSelectors.listContainer)
                    .then(listing => {
                        expect(listing).to.have.length(listsTotal + 1);
                    });

                // TODO: Check inputs are visible, has set values
                // TODO: Change values
                // TODO: Click save
                // TODO: Check new values, Nam URI?
            });

            it('Delete lists', function () {
                let listsTotal;

                // TODO: Create list

                cy.loginAsAdmin();
                cy.visit(boURL.settingsList);
                cy.url().should('include', 'section=taoBo_list');

                cy.get(boSelectors.listContainer)
                    .then(listing => {
                        listsTotal = Cypress.$(listing).length;
                        expect(listing).to.have.length(listsTotal);
                    });

                cy.intercept('GET', '**/taoBackOffice/Lists/getListElements').as('getElements');

                cy.get(boSelectors.deleteListButton)
                    .should('be.visible')
                    .click();

                cy.wait('@getElements');

                cy.get(boSelectors.listContainer)
                    .then(listing => {
                        expect(listing).to.have.length(listsTotal - 1);
                    });
            });
        });
    });
});

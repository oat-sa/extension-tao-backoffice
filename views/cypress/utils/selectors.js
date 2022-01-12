export default {
    listButton: 'a[title="Lists"]',

    lists: '[id^="list-data"]',
    listLast: '[id^="list-data"]:last-child',

    addItem: '[data-context="resource"][data-action="instanciate"]',

    maxItems: ['data-testid="maxItems"'],

    // List
    listName: '[data-testid="listName"]',
    createListButton: '#createList button',

    listEditButton: '[data-testid="listEditButton"]',
    listDeleteButton: '[data-testid="listDeleteButton"]',

    listNameInput: '[data-testid="listNameInput"]',
    uriElementsInput: ['id^="uri_list-element'],

    // Element
    elementsList: '[data-testid="elements"]',
    editUriCheckbox: '[data-testid="editUriCheckbox"]',
    elementNameInput: '[data-testid="elementNameInput"]',
    elementUriInput: '[data-testid="elementUriInput"]',


    addElementButton: '[data-testid="addElementButton"]',
    saveElementButton: '[data-testid="saveElementButton"]',
    deleteElementButton: '[data-testid="deleteElementButton"]',

};

// uri: "https://adf.docker.localhost/ontologies/tao.rdf#i61dccbca15c2f5063a57d51212f4b53"

// listLabelInput
// elementLabelInput
// elementUriInput
// addElementButton
/**
 * @namespace Adapt_resultPage
 */

/**
 *
 * @memberof Adapt_resultPage
 */
Adapt_resultPage = function () {

    this.grSmIndicatorAge = $('#grSmIndicatorAge');
    this.grSmIndicatorFloor = $('#grSmIndicatorFloor');

    this.queryAgeGrSm = $('#find_vulnerable_people_queryAgeGrSm');
    this.queryFloorGrSm = $('#find_vulnerable_people_queryFloorGrSm');

    this.queryAge = $('#find_vulnerable_people_queryAge');
    this.queryFloor = $('#find_vulnerable_people_queryFloor');

    this.streetNameAutocompleteUrl = "{{ path('getStreetsForAutocomplete', {'emergencyId': selectedEmergency.id}) }}";

    this.refineQueryUrl = "{{ path('find_vulnerable_people', {'emergencyId': selectedEmergency.id }) }}";

    this.findPageInitialViewTextBox = $('#find_vulnerable_people_resultPageInitialView');
    this.resultPageInitialViewTextBox = $('#find_vulnerable_people_findPageInitialView');

    this.findModeTextBox = $('#find_vulnerable_people_findMode');

    this.resultsAsListViewContainer = $('#resultsAsListView');
    this.resultsOnMapViewContainer = $('#resultsOnMapView');

};

/**
 * Sets showAllEntities to 1 which causes entitiesPerPage to be set to unlimited in VulnerablePeopleController->showVulnerablePeopleResultsAction()
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.showAllEntitiesButtonClicked = function() {
    $("#find_vulnerable_people_showAllEntities").val(1);
    $("#find_vulnerable_people_currentPage").val(1);
    $("form[name='find_vulnerable_people']").submit();
};


/**
 * Sets the GrSm (Greater than / Smaller than)-indicator based on the value of the form field
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.updateAgeGrSmIndicator = function () {

    if ($("#find_vulnerable_people_queryAgeGrSm").val() == "smaller") {
        $(this.grSmIndicatorAge).text("≤");
        $(this.grSmIndicatorAge).attr("title", "less than");
    } else {
        $(this.grSmIndicatorAge).text("≥");
        $(this.grSmIndicatorAge).attr("title", "greater than");
    }
};

/**
 *
 * The function checks if the page-number input was valid - if it was valid the page is reloaded
 * @function goToPageButtonClicked
 * @memberof Adapt_resultPage
 * @param {event} event
 */
Adapt_resultPage.prototype.goToPageButtonClicked = function(event) {

    event.preventDefault();

    var page = parseInt($('#goto_page_input').val());

    var pagesTotal = parseInt("{{ pagesTotal }}");
    if (page < 1 || page > pagesTotal) {
        alert("Please enter a page between 1 and " + pagesTotal + ".");
    } else {
        $('#find_vulnerable_people_currentPage').val(page);
        $('form[name="find_vulnerable_people"]').submit();
    }
};


/**
 * Sets the GrSm (Greater than / Smaller than)-indicator based on the value of the form field
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.updateFloorGrSmIndicator = function () {

    if ($("#find_vulnerable_people_queryFloorGrSm").val() == "smaller") {
        $(this.grSmIndicatorFloor).text("≤");
        $(this.grSmIndicatorFloor).attr("title", "less than");
    } else {
        $(this.grSmIndicatorFloor).text("≥");
        $(this.grSmIndicatorFloor).attr("title", "greater than");
    }
};

var adapt_resultPage = new Adapt_resultPage();

/**
 *
 * @function initVariables
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.initVariables = function() {

    this.gtltZip = "gt";

    this.customPolygon = null;

};

/**
 * Binds the input listeners to trigger form-submits when query options were changes
 * @function initEventListeners
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.initEventListeners = function() {

    // Send query on when textbox changes value (is send after the focus was changed)
    $('.table-header-search input').change(function() {
        this.submitForm();
    });

    // Send query after select box changed it's value
    $('#find_vulnerable_people_safetyStatus').change(function() {
        this.submitForm();
    });

};

/**
 * Shows the tab with the list-view.
 * @function showResultsAsListViewTab
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.showResultsAsListViewTab = function () {
    $('.nav-tabs a[href="#resultsAsListView"]').tab('show');
};

/**
 * This is needed to allow jumping to step1 of the find page when the FindMode (Streets/Map) is clicked.
 * @function backToFindPageStep
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.backToFindPageStep = function(intStep) {
    if (parseInt(intStep) == 1) {
        var stepString = 'step1';
    } else {
        var stepString = 'step2';
    }

    $(this.findPageInitialViewTextBox).val(stepString);
    $('form[name="find_vulnerable_people"]').attr('action', this.refineQueryUrl);
    this.clearSearchFields();

    this.submitForm();
};

/**
 * Inits the content of the autocompleter on the street-field.
 * @function initAutocompletionForAddStreetField
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.initAutocompletionForAddStreetField = function () {
    $.getJSON(this.streetNameAutocompleteUrl, function (data) {
        $("#streetTextBox").typeahead({source: data, autoSelect: true});
    });
};

/**
 * Changes the Order-value for the form.
 * @memberof Adapt_resultPage
 * @param {string} orderKey
 * @param {string} orderValue
 * @function triggerOrderChange
 */
Adapt_resultPage.prototype.triggerOrderChange = function(orderKey, orderValue) {

    var currentOrderKey = $('#find_vulnerable_people_orderKey').val();
    var currentOrderValue = $('#find_vulnerable_people_orderValue').val();

    if (orderKey == currentOrderKey) {
        if (currentOrderValue == "ASC") {
            $('#find_vulnerable_people_orderValue').val("DESC");
        } else {
            $('#find_vulnerable_people_orderValue').val("ASC");
        }
    } else {
        $('#find_vulnerable_people_orderKey').val(orderKey);
        $('#find_vulnerable_people_orderValue').val("ASC");
    }
    this.submitForm();
};

/**
 * Clears all search fields
 * @function clearSearchFields
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.clearSearchFields = function() {
    $('#find_vulnerable_people_queryFirstName').val("");
    $('#find_vulnerable_people_queryLastName').val("");
    $('#find_vulnerable_people_queryAge').val("");
    $('#find_vulnerable_people_queryStreet').val("");
    $('#find_vulnerable_people_queryStreetNumber').val("");
    $('#find_vulnerable_people_queryZip').val("");
    $('#find_vulnerable_people_queryCity').val("");
    $('#find_vulnerable_people_queryFloor').val("");
    $('#find_vulnerable_people_queryRemarks').val("");
};

/**
 * Sets the field value for "resultPageInitialViewTextBox" to 'map' or 'streets'.
 * @memberof Adapt_resultPage
 * @param {string} initialViewString 'map' or 'table'
 * @function setResultPageInitialView
 *
 */
Adapt_resultPage.prototype.setResultPageInitialView = function(initialViewString) {
    if (initialViewString != "map" &&
        initialViewString != "table"
    ) {
        alert("setResultPageInitialView was called with an illegal parameter. (allowed:table|map|step1-map|step1-streets|step2)");
    }
    $(this.resultPageInitialViewTextBox).val(initialViewString);
};

/**
 * Triggers and reset of the search-fields.
 * @memberof Adapt_resultPage
 * @param {event} event
 * @function resetSearchFieldButtonClicked
 */
Adapt_resultPage.prototype.resetSearchFieldButtonClicked = function(event) {
    //prevents the form data to be send
    event.preventDefault();
    this.resetSearch();
};

/**
 * Clears the search field and re-submits the form to update the results.
 * @memberof Adapt_resultPage
 * @function resetSearch
 */
Adapt_resultPage.prototype.resetSearch = function() {
    this.setResultPageInitialView('table');
    this.clearSearchFields();
    this.submitForm();
};

/**
 * Changes the order-array for the given Key.
 * @memberof Adapt_resultPage
 * @param {string} orderKey
 * @param {string} orderValue
 * @function changeOrderArray
 */
Adapt_resultPage.prototype.changeOrderArray = function(orderKey, orderValue) {

    sortArray[orderKey] = orderValue;
    $(sortArrayField).val(JSON.stringify(sortArray));

    this.submitFilterForm();
};

/**
 * Submits the form.
 * @memberof Adapt_resultPage
 * @function submitForm
 */
Adapt_resultPage.prototype.submitForm = function() {
    this.setResultPageInitialView('table');
    $('form[name="find_vulnerable_people"]')[0].submit();
};

/**
 * Submits the filter form
 * @memberof Adapt_resultPage
 * @param {event} event
 * @function applySearchFieldButtonClicked
 */
Adapt_resultPage.prototype.applySearchFieldButtonClicked = function(event) {
    //prevents the form data to be send
    event.preventDefault();

    this.setResultPageInitialView('table');
    $('form[name="find_vulnerable_people"]').submit();
};

/**
 * This function changes currentPage value in the form to open that page on the next page-load
 * @function goToPage
 * @memberof Adapt_resultPage
 * @param int targetPageString
 * @function goToPage
 */
Adapt_resultPage.prototype.goToPage = function(targetPageString) {

    $('#find_vulnerable_people_currentPage').val(targetPageString);
    $('form[name="find_vulnerable_people"]').submit();

};

/**
 * Submits the form
 * @memberof Adapt_resultPage
 */
Adapt_resultPage.prototype.submitForm = function () {
    $('form[name="find_vulnerable_people"]').submit();
};

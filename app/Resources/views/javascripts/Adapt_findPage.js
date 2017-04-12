/**
 * Functions to navigate on the find page
 * @namespace Adapt_findPage
 */

Adapt_findPage = function() {
    this.findModeTextBox = $('#find_vulnerable_people_findMode');

    this.step1Container = $("#step1container");
    this.step2Container = $("#step2container");

    /**
     * @property {array} streetsArray - Stores the temporary streetList
     * @memberof Adapt_findPage
     */
    this.streetsArray = [];

    /**
     * @property {bool} streetsAlreadyLoaded - This is used to load the streetsList when the tab with streets was opened for the first time
     * @memberof Adapt_findPage
     */
    this.streetsAlreadyLoaded = false;

    this.streetListIds = [];

    /**
     * used in addAllStreetsInDatabaseToEmergencyStreetList
     * @property {string} getAllStreetsInDatabaseJSONUrl - URL path
     * @memberof Adapt_findPage
     */
    this.getAllStreetsInDatabaseJSONUrl = '{{ path("json_getAllStreetsInDatabase") }}';

    /**
     * @property {array} zipcodeAutocompletionArray - Array for the autocompleter in the addSpecial menu
     * @memberof Adapt_findPage
     */
    this.zipcodeAutocompletionArray = [];

    /**
     * @property {object} streetListInfoPanel - DOM element addStreetsByZipcodeTextBox
     * @memberof Adapt_findPage
     */
    this.addStreetsByZipcodeTextBox = $('#addStreetsByZipcodeTextBox');

    /**
     * @property {object} streetListInfoPanel - DOM element streetListInfoPanel
     * @memberof Adapt_findPage
     */
    this.streetListInfoPanel = $('#streetListInfoPanel');

};

var adapt_findPage = new Adapt_findPage();

/**
 *
 * Expands the height of the panel with the Emergency street list
 * @function expandEmergencyStreetList
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.expandEmergencyStreetList = function() {
    $('#emergencyStreetListPanelBody').css("max-height", "");
    $("#showCompleteEmergencyStreetListButton").attr("disabled", "disabled");
};

/**
 *
 * @function openTab
 * @memberof Adapt_findPage
 * @param {string} tabString The name of the tab
 */
Adapt_findPage.prototype.openTab = function(tabString) {

    if (tabString == 'streets') {
        $('#tabSelectByStreets').find('a').tab('show');

        this.setFindMode('streets');
        this.setResultPageInitialView('table');

        //Loads the streets list async.
        if (this.streetsAlreadyLoaded == false) {
            this.loadStreetsArray();
        }
    } else {
        $('#tabSelectByMap').find('a').tab('show');
        this.setFindMode('map');
        this.setResultPageInitialView('map');
    }
};

/**
 * Sets the text for the info panel
 * @function showTextOnInfoPanel
 * @memberof Adapt_findPage
 * @param {string} text
 */
Adapt_findPage.prototype.showTextOnInfoPanel = function(text) {
    $(this.streetListInfoPanel).html('<div class="alert alert-info"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + text + '</div>');
    $(this.streetListInfoPanel).alert();
};

/**
 * Sets the find-mode. It's used to determine what if the map-view is available on the results-page.
 * @function setFindMode
 * @memberof Adapt_findPage
 * @param {string} initialViewString 'streets' or 'map'
 */
Adapt_findPage.prototype.setFindMode = function(findModeString) {
    if (findModeString != "streets" && findModeString != 'map') {
        alert("setFindMode was called with an illegal parameter. (allowed:streets|map)");
    }
    $(this.findModeTextBox).val(findModeString);
};

/**
 * Sets the field value for "resultPageInitialViewTextBox" to 'map' or 'streets'.
 * @function setResultPageInitialView
 * @memberof Adapt_findPage
 * @param {string} initialViewString 'table' or 'map'
 */
Adapt_findPage.prototype.setResultPageInitialView = function(initialViewString) {
    if (initialViewString != "map" && initialViewString != "table") {
        alert("setResultPageInitialView was called with an illegal parameter. (allowed:table|map|step1-map|step1-streets|step2)");
    }
    $(this.resultPageInitialViewTextBox).val(initialViewString);
};

/**
 * Jumps to step1 or step1 on the find page
 * @function jumpToStep
 * @memberof Adapt_findPage
 * @param {string} stepString
 * @param {bool} calledManually
 */
Adapt_findPage.prototype.jumpToStep = function(stepString, calledManually) {
    if (stepString == "step1") {
        $(this.step1Container).show();
        $(this.step2Container).hide();
    } else {
        if ($(this.findModeTextBox).val() == 'streets') {

            if ($("input:checkbox[name='selectedStreets']:checked").length == 0) {
                if (calledManually == true) {
                    alert("The Emergency Street List is empty. At least one street has to be selected in order to get any results.");
                }
            } else {
                //Here the current street list and the selected items are set to the form fields
                this.updateSelectedStreetIdsFormField();
                this.updateStreetListIdsFormField();

                $(this.step1Container).hide();
                $(this.step2Container).show();
            }
            //Do nothing here - The user needs to select some streets

        } else {
            $(this.step1Container).hide();
            $(this.step2Container).show();
        }
    }
};

/**
 * This updates the streetListIds-field.
 * @function updateStreetListIdsFormField
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.updateStreetListIdsFormField = function() {

    var streetListIdsArray = [];

    $.each(this.streetsArray, function (streetId, streetObj) {
        streetListIdsArray.push(parseInt(streetId));
    });

    $('#find_vulnerable_people_streetListIds').val(JSON.stringify(streetListIdsArray));
};

/**
 * Function to trigger all (or none) checkboxes on Step 2
 * @function selectionHelperFunction
 * @memberof Adapt_findPage
 * @param {string} allOrNone "all" or "none"
 * @param {string} formFieldString
 */
Adapt_findPage.prototype.selectionHelperFunction = function(allOrNone, formFieldString) {
    if (allOrNone == 'all') {
        $($("#find_vulnerable_people_" + formFieldString)[0]).find('input[type="checkbox"]').each(function () {
            //uncheck all checkboxes
            $(this).prop('checked', true);
        });
    } else {
        $($("#find_vulnerable_people_" + formFieldString)[0]).find('input[type="checkbox"]').each(function () {
            //uncheck all checkboxes
            $(this).prop('checked', false);
        });
    }
};

/**
 * Adds the selected street to the (local) streetsArray.
 * @function addStreet
 * @memberof Adapt_findPage_streets
 * @param {event} event
 */
Adapt_findPage.prototype.addStreet = function (event) {
    event.preventDefault();

    if (adapt_streets.tmpStreetToBeAddedToStreetArray !== undefined) {

        if (typeof this.streetsArray[adapt_streets.tmpStreetToBeAddedToStreetArray.id] == "undefined") {
            this.streetsArray[adapt_streets.tmpStreetToBeAddedToStreetArray.id] = adapt_streets.tmpStreetToBeAddedToStreetArray;
            this.showTextOnInfoPanel("\"" + adapt_streets.tmpStreetToBeAddedToStreetArray.name + "\" was added to the Emergency Street List.");
            this.reloadStreetsTable();
        } else {
            this.showTextOnInfoPanel("\"" + adapt_streets.tmpStreetToBeAddedToStreetArray.name + "\" already is in the Emergency Street List below.");
        }

    } else {
        alert('Please select a street first.');
    }
};

/**
 * Sends the current street-list to the controller to persist it.
 * @function saveStreetListAsEmergencyStreetList
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.saveStreetListAsEmergencyStreetList = function () {
    var saveStreetListAsEmergencyStreetListUrl = "{{ path('saveStreetListAsEmergencyStreetList', {'emergencyId': selectedEmergency.id} ) }}";

    var streetIdsArray = [];

    //Collect IDs of the streets in the streetsArray
    for (var i = 0; i < Object.keys(this.streetsArray).length; i++) {
        var streetId = Object.keys(this.streetsArray)[i];
        streetIdsArray.push(streetId);
    }

    var postData = {streetsArray: JSON.stringify(streetIdsArray)};

    $.post(saveStreetListAsEmergencyStreetListUrl, postData, function (data) {
        adapt_findPage.showTextOnInfoPanel("The street list was saved as the Emergency Street List for this emergency.");
    });
};

/**
 * Loads the streets list from the database and resets it.
 * @function resetStreetsListToSavedEmergencyStreetsList
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.resetStreetsListToSavedEmergencyStreetsList = function() {
    this.streetListIds = [];
    this.loadStreetsArray();
    $(this.streetListInfoPanel).hide();
};

/**
 * Gets the streetsArray from the database.
 * @function loadStreetsArray
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.loadStreetsArray = function() {

    //Shows a text so that the user knows the system is doing something...
    $('#streetsTable').removeClass('table-hover');
    $('#streetsTable tbody').html("<tr><td colspan='5'>Please wait...</td></tr>");

    if (this.streetListIds.length == 0) {
        var loadStreetsUrl = "{{ path('json_getStreetNamesForEmergency', {'emergencyId': selectedEmergency.id}) }}";

        var that = this;
        $.getJSON(loadStreetsUrl, function (data) {
            that.streetsArray = data;
            that.reloadStreetsTable();
            that.streetsAlreadyLoaded = true;
        });
    } else {
        var getStreetsByStreetListIdsUrl = "{{ path('json_getStreetsByStreetListIds', {'streetListIdsArray':'REPLACE_BY_JS'} ) }}";
        $.getJSON(getStreetsByStreetListIdsUrl, { streetListIdsArray: JSON.stringify(that.streetListIds) }, function (data) {
            that.streetsArray = data;
            that.reloadStreetsTable();
            that.streetsAlreadyLoaded = true;
        });
    }
};

/**
 *
 * @function openAddSpecialModal
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.openAddSpecialModal = function() {
    $('#addSpecialModal').modal('show');

    //clear the zipcode string
    $(this.addStreetsByZipcodeTextBox).val("");
    //removes the disabled state
    $(this.addStreetsByZipcodeTextBox).prop("disabled", false);

    $('input[name="selectedStreets_ForAddByZipcode"]').each(function () {
        //uncheck all checkboxes
        $(this).prop('checked', false);
        //remove disable attribute on the checkbox
        $(this).prop("disabled", false);
    });
};

/**
 *
 * @function reloadStreetsTable
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.reloadStreetsTable = function() {

    $('#streetsTable tbody').html("");

    var rows = [];

    if (Object.keys(this.streetsArray).length == 0) {
        rows.push("<tr><td colspan='5'>The Emergency Street List is empty.</td></tr>");
        $('#streetsTable').removeClass('table-hover');
    } else {
        $('#streetsTable').addClass('table-hover');
        for (var i=0; i<Object.keys(this.streetsArray).length; i++) {

            var streetId = Object.keys(this.streetsArray)[i];
            var streetObj = this.streetsArray[streetId];

            var checkedString;
            if (this.selectedStreetIdCheckboxes != null && this.selectedStreetIdCheckboxes.length > 0) {
                if (this.selectedStreetIdCheckboxes.indexOf(parseInt(streetId)) != -1) {
                    checkedString = ' checked ';
                } else {
                    checkedString = '';
                }
            } else {
                //If selectedStreetIdCheckboxes-Array is set everything is selected by default
                checkedString = ' checked ';
            }

            if (this.hasRoleDataAdmin) {
                var removeColumn = "<td class='centered-horizontal actions_column'> <a href='#' class='hasGlyphicon'><span onclick='adapt_findPage.removeStreetById(" + streetId + ")' class='glyphicon glyphicon-trash' aria-hidden='true'></span></a></td>";
            } else {
                var removeColumn = "";
            }
            var tmpString = "<tr value=" + streetId + "><td class='centered-horizontal'><label><input type='checkbox' name='selectedStreets' " + checkedString + " value=" + streetId + "></label></td><td>" + streetObj.name + "</td><td>" + streetObj.zipcode + "</td><td>" + streetObj.city + "</td>" + removeColumn + "</tr>";

            rows.push(tmpString);
        }
    }
    $('#streetsTable tbody').html("<fieldset id='selectedStreets'" + rows.join("") + "</fieldset>");
};

/**
 *
 * @function removeStreetById
 * @memberof Adapt_findPage
 * @param {int} streetIdToRemove
 */
Adapt_findPage.prototype.removeStreetById = function(streetIdToRemove) {

    var that = this;
    var tmpStreetName;
    $.each(this.streetsArray, function (streetId, streetObj) {
        if (streetId == streetIdToRemove) {
            tmpStreetName = streetObj.name;

            delete that.streetsArray[streetIdToRemove];
        }
    });

    this.showTextOnInfoPanel("\"" + tmpStreetName + "\" was removed from the Emergency Street List.");

    this.reloadStreetsTable();
};

/**
 * Adds all streets from the data base to the (local) emergency-street-list.
 * @function addAllStreetsInDatabaseToEmergencyStreetList
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.addAllStreetsInDatabaseToEmergencyStreetList = function() {

    var that = this;
    $.getJSON(this.getAllStreetsInDatabaseJSONUrl, function (data) {
        var newStreetsCounter = 0;
        //add data to streetsArray
        $.each(data, function (streetId, streetObj) {
            if (typeof that.streetsArray[streetId] == 'undefined') {
                newStreetsCounter++;
            }
            that.streetsArray[streetId] =  streetObj;
        });

        that.showTextOnInfoPanel( newStreetsCounter + " streets from the database were database were added to the Emergency Street List.");
        that.reloadStreetsTable();
    });
};

/**
 * This function builds collects an array of selected zipcodes and passes it so the addStreetsBySelectedZipcodesArray function
 * @function addStreetsBySelectedZipcodes
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.addStreetsBySelectedZipcodes = function() {

    var selectedZipcodesArray = [];
    $('input[name="selectedStreets_ForAddByZipcode"]:checked').each(function () {
        selectedZipcodesArray.push($(this).val());
    });

    //Add id from the user-input text field
    var userZipcodeString = $(this.addStreetsByZipcodeTextBox).val();
    for (var i=0; i<this.zipcodeAutocompletionArray.length; i++) {
        if (this.zipcodeAutocompletionArray[i].name == userZipcodeString) {
            selectedZipcodesArray.push(this.zipcodeAutocompletionArray[i].id);
            continue;
        }
    }
    this.addStreetsBySelectedZipcodesArray(selectedZipcodesArray);
};


/**
 *
 * @function initAutocompletionForAddStreetsByZipcodeField
 * @memberof Adapt_findPage
 * @param {array} data
 */
Adapt_findPage.prototype.initAutocompletionForAddStreetsByZipcodeField = function(data) {
    $(this.addStreetsByZipcodeTextBox).typeahead({source: data, autoSelect: true});
    this.zipcodeAutocompletionArray = data;
};


/**
 *
 * @function getZipcodesFromDatabase
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.getZipcodesFromDatabase = function() {

    var zipcodeAutocompleteUrl = "{{ path('json_getZipcodesForAutocomplete', {'emergencyId': selectedEmergency.id}) }}";

    var that = this;

    //zip and city are combined like "50858 Köln"
    $.getJSON(zipcodeAutocompleteUrl, function (data) {
        that.initAutocompletionForAddStreetsByZipcodeField(data);
    });

    var getZipcodesForAddSpecialUrl = "{{ path('json_getZipcodesForAddSpecial', {'emergencyId': selectedEmergency.id}) }}";

    $.getJSON(getZipcodesForAddSpecialUrl, function (data) {
        that.addDataToAddSpecial(data);
    });
};

/**
 *
 * @function addDataToAddSpecial
 * @memberof Adapt_findPage
 * @param {array} data
 */
Adapt_findPage.prototype.addDataToAddSpecial = function(data) {
    var rows = [];
    rows.push("<table class='table table-bordered table-striped'><thead><tr><th style='width: 50px;'>Select</th><th>Zipcode</th><th>City</th></tr></thead>");
    rows.push("<tbody>");
    $.each(data, function (key, value) {
        rows.push("<tr value=" + value.id + "><td class='centered-horizontal'><label><input type='checkbox' name='selectedStreets_ForAddByZipcode' value=" + value.id + "></label></td><td>" + value.zipcode + "</td><td>" + value.city + "</td></tr>");
    });

    rows.push("</tbody>");
    rows.push("</table>");

    $('#addSpecial_zipcodes_container').html(rows.join(""));

    this.addListenersForAddSpecial();
};

/**
 *
 * @function addListenersForAddSpecial
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.addListenersForAddSpecial = function() {
    //If the user checked a checkbox the input-box is disabled

    var that = this;
    $('input[name="selectedStreets_ForAddByZipcode"]').on('change', function() {
        if ($('input[name="selectedStreets_ForAddByZipcode"]:checked').length != 0) {
            $(that.addStreetsByZipcodeTextBox).prop("disabled", true);
        } else {
            $(that.addStreetsByZipcodeTextBox).prop("disabled", false);
        }
    });
};



/**
 * This function adds all streets of a list of zipcodes to the emergency streets list.
 * @function addStreetsBySelectedZipcodesArray
 * @memberof Adapt_findPage
 * @param {array} selectedZipcodesArray array with zipcode-IDs
 */
Adapt_findPage.prototype.addStreetsBySelectedZipcodesArray = function(selectedZipcodesArray) {

    var json_getAllStreetsByZipcode = '{{ path("json_getAllStreetsByZipcode", {zipcodeIdsArray : "TO_BE_REPLACED_WITH_JS"}) }}';
    json_getAllStreetsByZipcode = json_getAllStreetsByZipcode.replace("TO_BE_REPLACED_WITH_JS", JSON.stringify(selectedZipcodesArray));

    var addedZipcodesArray = [];
    $(selectedZipcodesArray).each(function(key, value) {
        addedZipcodesInfoString = "";
        var currentRow = $('#addSpecial_zipcodes_container tbody > tr[value=' + value + ']')[0];
        var currentZipcode = ($(currentRow).find(':nth-child(2)')).html();
        addedZipcodesArray.push(currentZipcode);
    });

    var that = this;
    $.getJSON(json_getAllStreetsByZipcode, function (data) {

        var newStreetsCounter = 0;

        //add data to streetsArray
        $.each(data, function (streetId, streetObj) {
            if (typeof that.streetsArray[streetId] == 'undefined') {
                newStreetsCounter++;
            }
            that.streetsArray[streetId] = streetObj;
        });

        selectedZipcodesArray = addedZipcodesArray.join(", ");
        if (selectedZipcodesArray.length > 1) {
            that.showTextOnInfoPanel(newStreetsCounter + " street(s) were added by zipcodes: " + selectedZipcodesArray);
        } else {
            that.showTextOnInfoPanel(newStreetsCounter + " street(s) were added by zipcode: " + selectedZipcodesArray);
        }

        that.reloadStreetsTable();
    });
};

/**
 *
 * @function updateSelectedStreetIdsFormField
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.updateSelectedStreetIdsFormField = function() {
    //get all selected streets
    var checkboxes = $("input:checkbox[name='selectedStreets']:checked");

    this.selectedStreetIdsArray = [];

    for (var i = 0; i < checkboxes.length; i++) {
        this.selectedStreetIdsArray.push(parseInt($(checkboxes[i]).val()));
    }

    $('#find_vulnerable_people_selectedStreetIds').val(JSON.stringify(this.selectedStreetIdsArray));
};

/**
 *
 * @function selectAllStreetIds
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.selectAllStreetIds = function() {
    $("input[name='selectedStreets']").prop("checked", true);
};

/**
 *
 * @function deselectAllStreetIds
 * @memberof Adapt_findPage
 */
Adapt_findPage.prototype.deselectAllStreetIds = function() {
    $("input[name='selectedStreets']").prop("checked", false);
};

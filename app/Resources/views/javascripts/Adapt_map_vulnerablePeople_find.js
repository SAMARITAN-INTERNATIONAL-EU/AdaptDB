/**
 * Extensions for Adapt_map that are only used on the findPage
 * @namespace Adapt_map
 */

    Adapt_map.prototype.streetsAlreadyLoaded = false;

   // this.customGeoAreas = [];

    //stores the currently displayed GeoAreas
    Adapt_map.prototype.currentGeoAreas = {};

    //stores the GeoAreas that were originally defined when the emergency was created
    //determined by name = "placeholdername for geoArea"
    Adapt_map.prototype.originalEmergencyGeoAreas = {};

    //used in showTextOnInfoPanel
    Adapt_map.prototype.streetListInfoPanel = $('#streetListInfoPanel');

    //used in updateActiveGeoAreaIdsArray
    Adapt_map.prototype.activeGeoAreaIdsArray = $('#find_vulnerable_people_activeGeoAreaIdsArray');

    Adapt_map.prototype.findPageInitialViewTextBox = $('#find_vulnerable_people_resultPageInitialView');
    Adapt_map.prototype.resultPageInitialViewTextBox = $('#find_vulnerable_people_findPageInitialView');

    Adapt_map.prototype.customGeoAreasArray_textBox = $('#find_vulnerable_people_customGeoAreasArray');

    Adapt_map.prototype.addStreetAlertBox = $('#addStreetAlertBox');

    Adapt_map.prototype.addStreetTextBox = $('#addStreetTextBox');

    //Important! Object not an array
    Adapt_map.prototype.streetsArray = {};

    //used in initGeoAreasForEmergencyContainer
    Adapt_map.prototype.geoAreasForEmergency = JSON.parse('{{ geoAreasForEmergencyJSON | raw}}');

    Adapt_map.prototype.emergencyGeoAreasArray = {};

    /**
     *
     * @function selectCheckboxesForGeoAreasForEmergency
     * @memberof Adapt_map
     */
    Adapt_map.prototype.selectCheckboxesForGeoAreasForEmergency = function() {
        for (var i=0; i<Object.keys(this.geoAreasForEmergency).length; i++) {
            var geoAreaId = Object.keys(this.geoAreasForEmergency)[i];
            var tmpGeoArea = this.geoAreasForEmergency[geoAreaId];
    
            //selects the corresponding checkboxes
            if (tmpGeoArea.name == "placeholdername for geoArea") {
                this.originalEmergencyGeoAreas[geoAreaId] = tmpGeoArea;
                $("input[name='fieldsetGeoAreas'][value='" + geoAreaId + "']").prop('checked', true).trigger("change");
            }
        }
    };
    
    /**
     * Adds geoAreas that are not user-defined to the array originalEmergencyGeoAreas
     *
     * @function initOriginalEmergencyGeoAreas
     * @memberof Adapt_map
     */
    Adapt_map.prototype.initOriginalEmergencyGeoAreas = function() {

        for (var i=0; i<Object.keys(this.geoAreasForEmergency).length; i++) {
            var geoAreaId = Object.keys(this.geoAreasForEmergency)[i];
            var tmpGeoArea = this.geoAreasForEmergency[geoAreaId];
            //selects the corresponding checkboxes
            if (tmpGeoArea.name == "placeholdername for geoArea") {
                this.originalEmergencyGeoAreas[geoAreaId] = tmpGeoArea;
            }
        }
    };
    
    /**
     *
     * @function addGeoArea
     * @memberof Adapt_map
     * @param {int} geoAreaId ID of the geoArea to be added
     */
    Adapt_map.prototype.addGeoArea = function(geoAreaId) {
        this.currentGeoAreas[geoAreaId] = this.geoAreasForEmergency[geoAreaId];
        this.drawPolygonsFromGeoAreas(this.currentGeoAreas);
        this.updateActiveGeoAreaIdsArray();
    };
    
    /**
     *
     * @function removeGeoArea
     * @memberof Adapt_map
     * @param {int} geoAreaId ID of the geoArea to be removed
     */
    Adapt_map.prototype.removeGeoArea = function(geoAreaId) {
        delete this.currentGeoAreas[geoAreaId];
        this.drawPolygonsFromGeoAreas(this.currentGeoAreas);
        this.updateActiveGeoAreaIdsArray();
    };
    
    /**
     *
     * @function updateActiveGeoAreaIdsArray
     * @memberof Adapt_map
     */
    Adapt_map.prototype.updateActiveGeoAreaIdsArray = function() {
        var tmpArrayActiveGeoAreaIds = Object.keys(this.currentGeoAreas);
        $(this.activeGeoAreaIdsArray).val(JSON.stringify(tmpArrayActiveGeoAreaIds));
    };
    
    /**
     * Adds (hidden) checkboxes - They are indented to show if the geoAreas from the Controller are "active" or the
     * user-defined geoAreas
     *
     * @function initGeoAreasCheckboxesContainer
     * @memberof Adapt_map
     */
    Adapt_map.prototype.initGeoAreasCheckboxesContainer = function() {
    
        fieldsetRows = [];
        $.each(this.geoAreasForEmergency, function(geoAreaId, geoArea) {
            fieldsetRows.push("<label><input type='checkbox' name='fieldsetGeoAreas' value='" + geoAreaId + "'>" + geoArea.name + "</label>");
        });
        $('#geoAreasCheckboxesContainer').html("<fieldset style='display: none;'>" + fieldsetRows.join("") + "</fieldset>");
    
        var that = this;
        $('input[name="fieldsetGeoAreas"][type="checkbox"]').on("change", function() {
            if ($($(this)[0]).prop('checked')) {
                that.addGeoArea($($(this)[0]).attr('value'));
            } else {
                that.removeGeoArea($($(this)[0]).attr('value'));
            }
        });
    };
    
    /**
     *
     * @function resetSelectionToEmergencyArea
     * @memberof Adapt_map
     */
    Adapt_map.prototype.resetSelectionToEmergencyArea = function() {
        this.hideEditingTools();
    
        //Resets the form-field so that no new geoArea will be persisted in the controller
        $(this.customGeoAreasArray_textBox).val("");
    
        //Compare arrays with object keys to determine if there is any action needed
        var currentGeoAreasNeedsUpdate = false;
    
        var objectKeysCurrentGeoAreas = Object.keys(this.currentGeoAreas);
        var objectKeysOriginalEmergencyGeoAreas = Object.keys(this.originalEmergencyGeoAreas);
    
        for (var i=0; i<objectKeysOriginalEmergencyGeoAreas.length; i++) {
            if (objectKeysCurrentGeoAreas[i] != objectKeysOriginalEmergencyGeoAreas[i]) {
                currentGeoAreasNeedsUpdate = true;
                continue;
            }
        }
    
        if (currentGeoAreasNeedsUpdate == true) {
            this.currentGeoAreas = {};
    
            //Disable all checkboxes
            //trigger not needed here, because the array is also resetted.
            $("input[name='fieldsetGeoAreas'][type='checkbox']").removeAttr('checked');
    
            var emergencyGeoAreaId;
            for (var j = 0; j < Object.keys(this.originalEmergencyGeoAreas).length; j++) {
                emergencyGeoAreaId = Object.keys(this.originalEmergencyGeoAreas)[j];
                $("input[name='fieldsetGeoAreas'][value='" + emergencyGeoAreaId + "']").prop('checked', true).trigger("change");
            }
        }
    };
    
    /**
     * Sets the field value for "findPageInitialViewTextBox" to step1 or step2.
     * @function setFindPageInitialView
     * @memberof Adapt_map
     * @param {string} initialViewString 'step1' or 'step2'
     */
    Adapt_map.prototype.setFindPageInitialView = function(initialViewString) {
        if (initialViewString != "step1" &&
            initialViewString != "step2") {
            alert("setFindPageInitialView was called with an illegal parameter. (allowed:table|map|step1-map|step1-streets|step2)");
        }
        $(this.findPageInitialViewTextBox).val(initialViewString);
    };

    
    /**
     * LeafletJS polygon tool is triggered to allow the user to draw.
     * @function redefineEmergencyAreaBasedOnSelection
     * @memberof Adapt_map
     */
    Adapt_map.prototype.redefineEmergencyAreaBasedOnSelection = function() {
    
        this.showEditingTools();
    
        //Unchecks all checkboxes and triggers the change event to update the geoAreaArray
        $("input[name='fieldsetGeoAreas'][type='checkbox']").prop('checked', false).trigger("change");
    
        //Activates the polygon editing tool
        $('.leaflet-draw-draw-polygon')[0].click();
    };

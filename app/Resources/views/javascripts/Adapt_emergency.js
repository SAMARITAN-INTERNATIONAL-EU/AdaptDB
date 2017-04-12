/**
 *
 * @namespace Adapt_emergency
 */

/**
 * Functions related to emergency-related pages
 *
 * @memberof Adapt_emergency
 */
function Adapt_emergency() {
}

var adapt_emergency = new Adapt_emergency();

/**
 * Shows a warning if no geo-areas are defined
 *
 * @memberof Adapt_address_overlay
 * @function showEditAddressOverlay
 */
Adapt_emergency.prototype.submitNewEmergencyForm = function(event) {

    if (adapt_map.latlngsArray === undefined || adapt_map.latlngsArray.length == 0) {

        var answer = confirm("For the emergency no geo-area is defined. It is strongly recommended that every emergency has an geo-area. Rescue Workers will have access to all persons in the database while this emergency is active. Do you want to continue without an geo-area?");

        if (answer == false) {
            event.preventDefault();
        }
    }

};

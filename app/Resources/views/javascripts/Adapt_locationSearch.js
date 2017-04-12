/**
 * @namespace Adapt_locationSearch
 */

/**
 * Logic for the location-search panel
 * @function Adapt_locationSearch
 * @memberof Adapt_locationSearch
 */
function Adapt_locationSearch() {

    this.emergencyCoordinatesStringField = $('#emergency_coordinatesString');

    this.locationSearchResults_container_visible = false;
}

var adapt_locationSearch = new Adapt_locationSearch();

    /**
     * Queries the geoNames service for the given zipcode and/or name.
     * When the data are returned by the service the data are set as a parameter for the callback function.
     * @function startLocationSearch
     * @param {string} locationSearchByZipcodeString
     * @param {string} locationSearchByNameString
     * @param {function} callback
     * @memberof Adapt_locationSearch
     */
    Adapt_locationSearch.prototype.startLocationSearch = function (locationSearchByZipcodeString, locationSearchByNameString, callback)
    {

        if (locationSearchByZipcodeString.length >= 2 || locationSearchByNameString.length >= 2) {

            //Shows the results table after the first search.
            if (this.locationSearchResults_container_visible == false) {
                $('#locationSearchResults_container').show();
                this.locationSearchResults_container_visible = true;
            }

            if (locationSearchByZipcodeString.length >= 2) {
                var queryPartZipCode = "&postalcode=" + locationSearchByZipcodeString;
            } else {
                var queryPartZipCode = "";
            }

            if (locationSearchByNameString.length >= 2) {
                var queryPartName = "&placename=" + locationSearchByNameString
            } else {
                var queryPartName = "";
            }

            var queryUrl = "http://api.geonames.org/postalCodeSearchJSON?&maxRows=50&username={{ geoNamesUsername }}" + queryPartZipCode + queryPartName;
            $.getJSON(queryUrl, function (data) {
                callback(data);
            });

        } else {
            alert("You have to enter a search-term with at least 2 characters in one of the fields \"Name\" or \"Zipcode\".")
        }
    };

    /**
     * This function collects the query parameters and passes them to LocationSearch. The hook-function adds the results to a table.
     * @param {event} event
     * @memberof Adapt_locationSearch
     */
    Adapt_locationSearch.prototype.sendQuery = function(event) {
        event.preventDefault();

        var locationSearchByZipcodeString = $('#locationSearchByZipcodeTextBox').val();
        var locationSearchByNameString = $('#locationSearchByNameTextBox').val();

        this.startLocationSearch(locationSearchByZipcodeString, locationSearchByNameString, function (data) {

            var rows = [];
            $.each(data.postalCodes, function (key, val) {
                rows.push("<tr onclick='adapt_map.showCoordinatesOnMap(" + val.lat + "," + val.lng + ")'><td>" + val.placeName + "</td><td>" + val.postalCode + "</td><td>" + val.countryCode + "</td></tr>");
            });
            var rowsString = rows.join("");
            $('#locationSearchTable').html('<tbody>' + rowsString + '</tbody>');

        });
    };

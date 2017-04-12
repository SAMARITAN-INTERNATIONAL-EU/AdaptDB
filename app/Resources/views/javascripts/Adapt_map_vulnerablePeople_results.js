/**
 * Extensions for Adapt_map that are only used on the resultsPage
 * @memberof Adapt_map
 */

Adapt_map.prototype.addressesArrayForMarkerCluster = JSON.parse('{{ addressesArrayForMarkerClusterJSON | raw }}');

/**
 *
 * @function showPersonsMarkerCluster
 * @memberof Adapt_map
 */
Adapt_map.prototype.showPersonsMarkerCluster = function() {
    var markers = new L.MarkerClusterGroup();

    if (this.addressesArrayForMarkerCluster.length != 0 && this.map != null) {

        for (var i = 0; i < this.addressesArrayForMarkerCluster.length; i++) {
            var personAddress = this.addressesArrayForMarkerCluster[i];
            var address = personAddress['address'];
            if (address['geopoint'] != null) {

                var popupContentString = this.getPopupContentFromPersonAddress(personAddress);

                markers.addLayer(L.marker([address['geopoint']['lat'], address['geopoint']['lng']])
                    .bindPopup(popupContentString)
                    .openPopup());
            }
        }
    }

    this.map.addLayer(markers);
};

/**
 * Gets the html-content of the MarkerCluster popup
 *
 * @function getPopupContentFromPersonAddress
 * @memberof Adapt_map
 */
Adapt_map.prototype.getPopupContentFromPersonAddress = function(personAddress) {
    var address = personAddress['address'];
    var person = personAddress['person'];
    var returnString =
        person['first_name'] +
        " " + person['last_name'];

    if (person['date_of_birth'] != null) {
        returnString += "<br>" + person['date_of_birth'];
    }

    returnString += "<br>" + address['street']['name'] + " " + address['house_nr'] +
        "<br>" + address['street']['zipcode']['zipcode'] +
        " " + address['street']['zipcode']['city'];

    if (personAddress['floor'] != null) {
        returnString += " " + "<br> Floor: " + personAddress['floor'];
    }

    var showPersonUrlForPerson = this.showPersonUrl.replace("PERSONIDTOBEREPLACED", personAddress['person']['id']);
    returnString += "<br> <a target='_blank' href=" +  showPersonUrlForPerson + "><span class='glyphicon glyphicon-user'' aria-hidden='true'></span> Show Person</a>";

    return returnString;
};
/**
 *
 * @namespace Adapt_map
 */

function Adapt_map() {

    /**
     * @property {array} array of the defined geoAreas
     * @memberof Adapt_map
     */
    this.emergencyGeoAreaPolygons = {};

    this.latlngsArray = [];

    /**
     * @property {array} emergencyGeoAreaPolygons - set in drawPolygonsFromGeoAreas
     * @memberof Adapt_map
     */
    this.currentlyDrawnPolygons = {};
    
    this.emergencyCoordinatesStringField = $('#emergency_coordinatesString');

    this.drawnItems = new L.FeatureGroup();

    var outerFunctionThis = this;
    //Defines the map options for polygon-editing
    this.drawControlPolygon = new L.Control.Draw({
        edit: {
            featureGroup: outerFunctionThis.drawnItems
        },
        draw: {
            //defines the tools that are available for drawing shapes
            polygon: {
                shapeOptions: {
                    color: "#0000ff"
                }
            },
            polyline: false,
            rectangle: false,
            circle: false,
            marker: false
        }
    });

    this.leafletPolygonTool = $('a.leaflet-draw-draw-polygon');
    this.leafletEditTool = $('a.leaflet-draw-edit-edit');
    this.leafletRemoveTool = $('a.leaflet-draw-edit-remove');

    //Defines the map options for editing and deleting shapes
    this.drawControlEditOnly = new L.Control.Draw({
        edit: {
            featureGroup: outerFunctionThis.drawnItems
        },
        draw: false
    });
}

var adapt_map = new Adapt_map();

/**
 * Updates the latlngsArray to be the user defined polygon
 * @memberof Adapt_map
 */
Adapt_map.prototype.updateLatLngsArray = function() {

    for (var layerKey in this.drawnItems._layers) {
        leafletPolygon = this.drawnItems._layers[layerKey];
        this.latlngsArray.push(leafletPolygon['_latlngs']);
    }
};

/**
 * Inits the leafet plugin
 * @memberof Adapt_map
 */
Adapt_map.prototype.initLeafletJSMap = function() {

    // Gets default parameters
    // This call prevents passing parameters from many placed to this place

    {{ js_defaults_service.adaptMapDefaults() | raw }}

    this.map = L.map('map').setView([defaultMapCoordinatesArray[0], defaultMapCoordinatesArray[1]], defaultMapZoomLevel);

    var maxZoomLevel = 18;
    var minZoomLevel = 1;

    switch (mapVariant) {
        case "OpenStreetMap.DE":
            L.tileLayer('//{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png', {
                maxZoom: maxZoomLevel,
                minZoom: minZoomLevel,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(this.map);
            break;
        case "OpenStreetMap.Mapnik":
            L.tileLayer('//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: maxZoomLevel,
                minZoom: minZoomLevel,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(this.map);
            break;
        case "OpenStreetMap.HOT":
            L.tileLayer('//{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                maxZoom: maxZoomLevel,
                minZoom: minZoomLevel,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>'
            }).addTo(this.map);
            break;
        default: //defaults to "OpenStreetMap.DE"
            L.tileLayer('//{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png', {
                maxZoom: maxZoomLevel,
                minZoom: minZoomLevel,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(this.map);
            break;
    }
};

/**
 * Initializes leafletJS and defined hook functions that are called when the user draws, edits or deleted an polygon.
 * @memberof Adapt_map
 * @param {function} hookFunctionCreated - Hook function that is called when the created event has fired.
 * @param {function} hookFunctionEdited - Hook function that is called when the edited event has fired.
 * @param {function} hookFunctionDeleted - Hook function that is called when the deleted event has fired.
 */
Adapt_map.prototype.initLeaftletJSWithDrawPanels = function(hookFunctionCreated, hookFunctionEdited, hookFunctionDeleted) {

    this.map.addLayer(this.drawnItems);

    this.map.addControl(this.drawControlPolygon);

    var that = this;
    this.map.on('draw:created', function (e) {
        var type = e.layerType;
        var layer = e.layer;
        layer.options.color = "#0000ff";
        that.drawnItems.addLayer(layer);
        that.updateLatLngsArray();
        if (hookFunctionCreated) {
            hookFunctionCreated(layer);
        }
    });

    this.map.on('draw:edited', function (e) {
        var layer = e.layer;
        that.updateLatLngsArray();
        if (hookFunctionEdited) {
            hookFunctionEdited(e);
        }
    });

    this.map.on('draw:deleted', function (e) {
        var deletedLayersArray = e.layers._layers;
        if (hookFunctionDeleted) {
            hookFunctionDeleted(deletedLayersArray);
        }
    });
};

/**
 * Triggers the leaflet-tools to be hidden
 * @memberof Adapt_map
 */
Adapt_map.prototype.hideEditingTools = function() {
    $(this.leafletPolygonTool).hide();
    $(this.leafletEditTool).hide();
    $(this.leafletRemoveTool).hide();
};

/**
 * Triggers the leaflet-tools to be showed
 * @memberof Adapt_map
 */
Adapt_map.prototype.showEditingTools = function() {
    $(this.leafletPolygonTool).show();
    $(this.leafletEditTool).show();
    $(this.leafletRemoveTool).show();
};

/**
 * Adds Polygons to the map for all GeoAreas in geoAreasArray
 * @memberof Adapt_map
 */
Adapt_map.prototype.drawPolygonsFromGeoAreas = function(geoAreasArray) {

    //Removes all polygons from the map
    for (var i=0; i<Object.keys(this.currentlyDrawnPolygons).length; i++) {
        var polygonToBeRemoved = this.currentlyDrawnPolygons[Object.keys(this.currentlyDrawnPolygons)[i]];
        this.map.removeLayer(polygonToBeRemoved);
    }

    this.currentlyDrawnPolygons = {};

    //Adds new polygons to the map - based on the Array
    for (var i=0; i<Object.keys(geoAreasArray).length; i++) {
        var geoArea = geoAreasArray[Object.keys(geoAreasArray)[i]];

        var tmpGeoArea = new L.polygon(geoArea['geoPoints'], {
            color: '#0000ff',
            weight: 3,
            opacity: 0.5,
            smoothFactor: 1
        });

        tmpGeoArea.addTo(this.map);
        this.currentlyDrawnPolygons[tmpGeoArea['_leaflet_id']] = tmpGeoArea;
        this.emergencyGeoAreaPolygons[tmpGeoArea['_leaflet_id']] = tmpGeoArea;
    }
};

/**
 * Adds a polygon to the currentlyDrawnPolygons-array
 * @memberof Adapt_map
 */
Adapt_map.prototype.addPolygonToCurrentlyDrawnPolygons = function(polygon) {
    this.currentlyDrawnPolygons[polygon['_leaflet_id']] = polygon;
};

/**
 * Centers the map on the center point of all geoAreas in geoAreasArray
 * @memberof Adapt_map
 * @param {array} geoAreasArray - An array of geoAreas to be centered on the map
 */
Adapt_map.prototype.centerGeoAreasArray = function(geoAreasArray) {

    var geoPointsArray = [];
    for (var g = 0; g < geoAreasArray.length; g++) {
        if (typeof geoAreasArray[g]['geoPoints'] != 'undefined') {
            $.merge(geoPointsArray, geoAreasArray[g]['geoPoints']);
        } else {
            $.merge(geoPointsArray, geoAreasArray[g]);
        }
    }

    var tmp = this.getMinAndMaxCoordinates(geoPointsArray);
    var bounds = [[tmp["maxLat"], tmp["maxLon"]], [tmp["minLat"], tmp["minLon"]]];

    this.map.fitBounds(bounds);
};

/**
 * Moves the map view to the given coordinate.
 * @param {float} lat
 * @param {float} lng
 * @memberof Adapt_map
 */
Adapt_map.prototype.showCoordinatesOnMap = function(lat, lng) {

    adapt_map.map.panTo(new L.latLng(lat, lng));
};

Adapt_map.prototype.getMinAndMaxCoordinates = function(geoPointsArray) {

    minLat = 9007199254740991;
    minLon = 9007199254740991;
    maxLat = -9007199254740991;
    maxLon = -9007199254740991;

    //Determines the min and max points of the given geoArea
    for (var i = 0; i < geoPointsArray.length; i++) {

        currentGeoPoint = geoPointsArray[i];

        if (currentGeoPoint['lng'] > maxLon) {
            maxLon = currentGeoPoint['lng'];
        }

        if (currentGeoPoint['lng'] < minLon) {
            minLon = currentGeoPoint['lng'];
        }

        if (currentGeoPoint['lat'] > maxLat) {
            maxLat = currentGeoPoint['lat'];
        }

        if (currentGeoPoint['lat'] < minLat) {
            minLat = currentGeoPoint['lat'];
        }
    }

    return {
        "minLat" : minLat,
        "maxLat": maxLat,
        "minLon": minLon,
        "maxLon": maxLon
    }
};

/**
 * Centers the view position of to the given geoPointsArray
 * @param {array} geoPointsArray
 * @memberof Adapt_map
 */
Adapt_map.prototype.centerGeoPointsArray = function(geoPointsArray) {

    if (geoPointsArray != null && geoPointsArray.length >= 1) {

        var tmp = this.getMinAndMaxCoordinates(geoPointsArray);

        var bounds = [[tmp["maxLat"], tmp["maxLon"]], [tmp["minLat"], tmp["minLon"]]];
        this.map.fitBounds(bounds);
    }
};


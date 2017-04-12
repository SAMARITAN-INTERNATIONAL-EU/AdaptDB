/**
 *
 * @namespace Adapt_streets
 */

/**
 * @memberof Adapt_streets
 */
Adapt_streets = function() {};

var adapt_streets = new Adapt_streets();

/**
 * Inits the autocompleter for the "add street" textbox. Relies on the
 * @memberof Adapt_streets
 */
Adapt_streets.prototype.initAddSteetTextBox = function() {

    var that = this;
    $(addStreetTextBox).typeahead({
        minLength: 3,
        source: function (query, process) {
            var getStreetsByNameForAutocompleteUrl = "{{ path('json_getStreetsByNameForAutocomplete', {searchString: 'TO_BE_REPLACED'}) }}";
            getStreetsByNameForAutocompleteUrl = getStreetsByNameForAutocompleteUrl.replace("TO_BE_REPLACED", query);

            return $.get(getStreetsByNameForAutocompleteUrl, null, function (data) {
                return process(data);
            });
        },
        //Without the matcher names that were found through it's normalized name would not be shown in the dropdown-menu
        matcher: function (item) {
            return true;
        },
        //The updater method is called after an item is selected from the list
        updater: function(item) {
            //This variable will be used when the add button is pressed
            that.tmpStreetToBeAddedToStreetArray = item;
            return item;
        },
        //Customizes the text shown in the dropdown-menu
        displayText: function(item) {
            return item.name + " (" + item.zipcode + " " + item.city + ")";
        }
    });
};

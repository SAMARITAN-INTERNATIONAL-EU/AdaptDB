/**
 * Methods for lists where persons are shown (Person-Addresses-Overview and the Find-Vulnerable-People-Result-page)
 * @namespace Adapt_person_lists
 *
 */

Adapt_person_lists = function() {

};

/**
 * This function adds a gap behind every potential identity
 *
 * @function addGapsAfterPotentialIdentities
 * @memberof Adapt_person_lists
 */
Adapt_person_lists.prototype.addGapsAfterPotentialIdentities = function() {
    var lastBelongsToPi = -1;

    $.each($("tr"), function(index, value)
    {
        var dataBelongsToPi = $(value).data("belongs-to-pi");

        if ((dataBelongsToPi != lastBelongsToPi) && index >=3) {
            $(value).before("<tr class='personAddress_gap_row'></tr>");
        }
        lastBelongsToPi = dataBelongsToPi;

    });
};

/**
 * Toggles the additional person-rows based on the data-expanded value of the td-element
 *
 * @function toggleShowAllPersonsOfPI
 * @memberof Adapt_person_lists
 */
Adapt_person_lists.prototype.toggleShowAllPersonsOfPI = function(event, piHelperId) {

    var target = $( event.target );
    if ( target.is( "td" ) ) {
        var $sender = $(event.target);
    } else {
        var $sender = $(event.target).parent();
    }

    if ($sender.data("expanded") == 1) {
        $sender.find("span.text").text("Show additional persons of this Potential Identity");

        // Hides the previously hidden person-rows
        $('tr[data-belongs-to-pi=' + piHelperId + '][data-pi-additional-person]').hide();

        $('tr[data-belongs-to-pi=' + piHelperId + '].personListToggleRow>td span.glyphicon')
            .removeClass("glyphicon-chevron-up")
            .addClass("glyphicon-chevron-down");

        $sender.data("expanded", 0);
    } else {
        $sender.find("span.text").text("Hide additional persons of this Potential Identity");

        // Shows the previously hidden person-rows
        $('tr[data-belongs-to-pi=' + piHelperId + '][data-pi-additional-person]').show();

        $('tr[data-belongs-to-pi=' + piHelperId + '].personListToggleRow>td span.glyphicon')
            .removeClass("glyphicon-chevron-down")
            .addClass("glyphicon-chevron-up");

        $sender.data("expanded", 1);
    }
};

var adapt_person_lists = new Adapt_person_lists();



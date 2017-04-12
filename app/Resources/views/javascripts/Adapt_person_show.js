/**
 * Functions for the "Showing Person..." page
 * @namespace Adapt_person_show
 */

/**
 * Extensions for the "show person"-page
 * @memberof Adapt_person_show
 */
Adapt_person_show = function() {

    this.toggleableIDColumn = $(".toggleableIDColumn");
    this.scrollToTransitionMilliseconds = 500;

    this.addPersonToPotentialIdentityTableBody = $('#AddPersonToPotentialIdentityTable tbody');
};

var adapt_person_show = new Adapt_person_show();

/**
 *
 * @function showCompleteDataChangeHistory
 * @memberof Adapt_person_show
 */
Adapt_person_show.prototype.showCompleteDataChangeHistory = function(e) {
    e.preventDefault();
    if (!e)
        e = window.event;
    var sender = e.srcElement || e.target;
    //disable the button "Show Complete Data Change History"
    $(sender).attr('disabled', 'disabled');

    $('#dataChangeHistoryPanelBody').css("max-height", "");

    var dataChangeHistoryPanelBody = $("#dataChangeHistoryContainer .panel-body");
    $(dataChangeHistoryPanelBody[0]).css("height", "initial");
    $(dataChangeHistoryPanelBody[0]).css("overflow-x", "initial");
    $(dataChangeHistoryPanelBody[0]).css("overflow-y", "initial");

};

/**
 *
 * @function showToggleableIdColumns
 * @memberof Adapt_person_show
 */
Adapt_person_show.prototype.showToggleableIdColumns = function () {
    $(this.toggleableIDColumn).show();
    $("#showIdColumsButton").hide();
    $("#hideIdColumsButton").show();

    if($("#contactPersonsContainer").length > 0) {
        $.scrollTo('#contactPersonsContainer', this.scrollToTransitionMilliseconds)
    } else {
        $.scrollTo('#personAddressesContainer', this.scrollToTransitionMilliseconds)
    }
};

/**
 *
 * @function hideToggleableIdColumns
 * @memberof Adapt_person_show
 */
Adapt_person_show.prototype.hideToggleableIdColumns = function () {
    $(this.toggleableIDColumn).hide();
    $("#hideIdColumsButton").hide();
    $("#showIdColumsButton").show();
};

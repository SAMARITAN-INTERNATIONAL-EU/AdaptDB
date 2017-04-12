/**
 * Provides a function to scroll to the warnings-section
 * @namespace Adapt_import_success
 */
Adapt_import_success = function() {
};

/**
 *
 * Triggers executeDetectAndDeletePotentialIdentitiesCommand and updates the alert on the success page once its finished
 *
 * @function updateDetectPiAlert
 * @memberof Adapt_import_success
 * @param {string} detectPotentialIdentitiesUrl
 */
Adapt_import_success.prototype.updateDetectPiAlert = function(detectPotentialIdentitiesUrl) {

    var detectPotentialIdentitiesAlert = $("#detectPotentialIdentitiesAlert");

    $.ajax(detectPotentialIdentitiesUrl)
        .done(function(data) {

            $(detectPotentialIdentitiesAlert).removeClass("alert-info");
            $(detectPotentialIdentitiesAlert).addClass("alert-success");
            $(detectPotentialIdentitiesAlert).html(data);
        });

    $(detectPotentialIdentitiesAlert).addClass("alert-info");
    $(detectPotentialIdentitiesAlert).html("Detecting Potential Identities...please wait");

};

var adapt_import_success = new Adapt_import_success();
/**
 * Methods the the "New Person"-page
 * @namespace Adapt_person_new
 *
 */

Adapt_person_new = function() {
    this.validUntilCheckbox = $('#validUntilCheckbox')[0];
    this.personValidUntilField = $('#person_address_person_validUntil')[0];
};

var adapt_person_new = new Adapt_person_new();

/**
 *
 * @function updatePersonValidUntilDisabledState
 * @memberof Adapt_person_new
 */
Adapt_person_new.prototype.updatePersonValidUntilDisabledState = function(that) {

    if ($(that.validUntilCheckbox).is(':checked')) {
        $(that.personValidUntilField).removeAttr('disabled');
    } else {
        $(that.personValidUntilField).val("");
        $(that.personValidUntilField).attr('disabled', 'disabled');
    }
};

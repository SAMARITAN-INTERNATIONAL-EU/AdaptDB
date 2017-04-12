/**
 *
 * @namespace Adapt_contactPerson_overlay
 */

/**
 *
 * Functions related to the edit-Contact Person overlay on the person-show page
 * @memberof Adapt_contactPerson_overlay
 */
function Adapt_contactPerson_overlay() {

    this.contactPersonOverlayContactPersonId = "";
    this.contactPersonNewActionUrl = "{{ path('contactperson_new') }}";
    this.contactPersonEditActionUrl = "{{ path('contactperson_edit', {'id': 'CONTACTPERSONID_TOBEREPLACED'}) }}";
    this.contactPersonDeleteActionUrl = "{{ path('contactperson_delete', {'id': 'CONTACTPERSONID_TOBEREPLACED'}) }}";
    this.personAddressDeleteActionUrl = "{{ path('personaddress_delete', {'personId': 'PERSONID_TOBEREPLACED', 'addressId': 'ADDRESSID_TOBEREPLACED'}) }}";

    this.contactPersonOverlayHeadline = $('#editContactPersonOverlay').find('.modal-title')[0];
    this.contactPersonOverlayLoadingIndicator = $('#editContactPersonOverlay').find('.loadingIndicator')[0];
    this.contactPersonOverlayContainer = $('#editContactPersonContainer');
    this.contactPersonOverlaySubmitButton = $('#contactPersonOverlaySubmitButton');

}

var adapt_contactPerson_overlay = new Adapt_contactPerson_overlay();


/**
 * Show a confirmation dialog before deleting a Contact-Person
 * @function showDeleteConfirmationForContactPerson
 * @param contactPersonId Id of the contact Person to be deleted
 * @param contactPersonName String to be displayed in the dialog
 * @memberof Adapt_contactPerson_overlay
 */
Adapt_contactPerson_overlay.prototype.showDeleteConfirmationForContactPerson = function(contactPersonId, contactPersonName) {
    var answer = confirm('Do you really want to delete Contact Person "' + contactPersonName + '"?');

    if (answer == true) {
        var url =this.contactPersonDeleteActionUrl.replace("CONTACTPERSONID_TOBEREPLACED", contactPersonId);

        $.post( url, function( data ) {
            if (data == "success") {
                location.reload();
            } else {
                alert('An error occurred while deleting the Contact Person.');
            }
        });
    }
};


/**
 * Show a confirmation dialog before deleting a PersonAddress
 * @function showDeleteConfirmationForPersonAddress
 * @param personId Id is used to generate the URL to redirect after the deletion
 * @param addressId Id of the contact Person to be deleted
 * @param addressDescriptionString String to be displayed in the dialog
 * @memberof Adapt_contactPerson_overlay
 */
Adapt_contactPerson_overlay.prototype.showDeleteConfirmationForPersonAddress = function(personId, addressId, addressDescriptionString) {
    var answer = confirm('Do you really want to delete Address "' + addressDescriptionString + '"?');

    if (answer == true) {
        var url = this.personAddressDeleteActionUrl.replace("PERSONID_TOBEREPLACED", personId);
        url = url.replace("ADDRESSID_TOBEREPLACED", addressId);

        $.post( url, function( data ) {
            if (data == "success") {
                location.reload();
            } else {
                alert('An error occurred while deleting the Address.');
            }
        });
    }
};


/**
 *
 * Function to load the Overlay
 * @function showEditContactPersonOverlay
 * @memberof Adapt_contactPerson_overlay
 */
Adapt_contactPerson_overlay.prototype.showEditContactPersonOverlay = function(event, contactPersonId) {

    event.preventDefault();

    $.scrollTo("#contactPersonsContainer", adapt_person_show.scrollToTransitionMilliseconds);

    $('#editContactPersonOverlay').modal('show');

    $(this.contactPersonOverlayLoadingIndicator).show();
    $(this.contactPersonOverlayContainer).hide();

    var that = this;
    if (contactPersonId == null) {

        this.contactPersonOverlayContactPersonId = "";
        $(this.contactPersonOverlayHeadline).html("Add Contact Person");
        $(this.contactPersonOverlaySubmitButton).html("Create");

        $.get( this.contactPersonNewActionUrl, function( data ) {
            $(that.contactPersonOverlayContainer).html(data);
            $('#contact_person_person').val(parseInt("{{person.id}}"));
            $(that.contactPersonOverlayLoadingIndicator).hide();
            $(that.contactPersonOverlayContainer).show();
        });

    } else {

        this.contactPersonOverlayContactPersonId = contactPersonId;
        $(this.contactPersonOverlayHeadline).html("Edit Contact Person");
        $(this.contactPersonOverlaySubmitButton).html("Save");

        var contactPersonEditActionUrlTmp = this.contactPersonEditActionUrl.replace("CONTACTPERSONID_TOBEREPLACED", contactPersonId);

        $.get( contactPersonEditActionUrlTmp, function( data ) {
            $(that.contactPersonOverlayContainer).html(data);
            $(that.contactPersonOverlayLoadingIndicator).hide();
            $(that.contactPersonOverlayContainer).show();
        });
    }
};


/**
 *
 * Function to submit the form in the overlay
 * @function contactPersonOverlaySubmit
 * @memberof Adapt_contactPerson_overlay
 */
Adapt_contactPerson_overlay.prototype.contactPersonOverlaySubmit = function (event) {
    event.preventDefault();

    //Change the url
    var url = "";
    if (this.contactPersonOverlayContactPersonId != "") {

        url = this.contactPersonEditActionUrl.replace("CONTACTPERSONID_TOBEREPLACED", this.contactPersonOverlayContactPersonId)
    } else {

        url = this.contactPersonNewActionUrl;
    }

    var that =  this;
    var datastring = $("form[name='contact_person']").serialize();
    $.ajax({
        type: "POST",
        url: url,
        data: datastring,
        success: function(data) {
            if (data == "success") {
                location.reload();
            } else {
                $(that.contactPersonOverlayContainer).html(data);
                $(that.contactPersonOverlayLoadingIndicator).hide();
                $(that.contactPersonOverlayContainer).show();
            }
        }
    });

    //Show the loading indicator after the post has been send
    $(this.contactPersonOverlayLoadingIndicator).show();
    $(this.contactPersonOverlayContainer).hide();
};
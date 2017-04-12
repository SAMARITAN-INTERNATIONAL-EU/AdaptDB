/**
 *
 * @namespace Adapt_address_overlay
 */

/**
 * Functions related to the edit-Address overlay on the person-show page
 *
 * @memberof Adapt_address_overlay
 */
function Adapt_address_overlay() {

    this.formName = "person_address_without_person";
    this.addressOverlayPersonAddressId = "";
    this.personAddressNewActionUrl = "{{ path('person_address_new', {'personId': person.id}) }}";
    this.personAddressEditActionUrl = "{{ path('person_address_edit', {personAddressId: 'PERSONADDRESSID_TOBEREPLACED'}) }}";

    this.addressOverlayHeadline = $('#editAddressOverlay').find('.modal-title')[0];
    this.addressOverlayLoadingIndicator = $('#editAddressOverlay').find('.loadingIndicator')[0];
    this.addressOverlayContainer = $('#editAddressContainer');
    this.addressOverlaySubmitButton = $('#addressOverlaySubmitButton');
}

var adapt_address_overlay = new Adapt_address_overlay();

/**
 *
 * @memberof Adapt_address_overlay
 * @function showEditAddressOverlay
 */
Adapt_address_overlay.prototype.showEditAddressOverlay = function(event, personAddressId) {

    event.preventDefault();
    $.scrollTo("#personAddressesContainer", adapt_person_show.scrollToTransitionMilliseconds);
    $('#editAddressOverlay').modal('show');

    $(this.addressOverlayLoadingIndicator).show();
    $(this.addressOverlayContainer).hide();

    var that = this;
    if (personAddressId == null) {

        this.addressOverlayPersonAddressId = "";
        $(this.addressOverlayHeadline).html("Add Address");

        $.get( this.personAddressNewActionUrl, function( data ) {
            $(that.addressOverlayContainer).html(data);
            $(that.addressOverlayLoadingIndicator).hide();
            $(that.addressOverlayContainer).show();
        });

    } else {

        this.addressOverlayPersonAddressId = personAddressId;
        $(this.addressOverlayHeadline).html("Edit Address");
        $(this.addressOverlaySubmitButton).html("Save");

        var personAddressEditActionUrlTmp = this.personAddressEditActionUrl.replace("PERSONADDRESSID_TOBEREPLACED", personAddressId);

        $.get( personAddressEditActionUrlTmp, function( data ) {
            $(that.addressOverlayContainer).html(data);
            $(that.addressOverlayLoadingIndicator).hide();
            $(that.addressOverlayContainer).show();
        });
    }
};

/**
 *
 * @memberof Adapt_address_overlay
 * @function addressOverlaySubmit
 */
Adapt_address_overlay.prototype.addressOverlaySubmit = function (event) {
    event.preventDefault();

    //Change the url
    var url = "";
    if (this.addressOverlayPersonAddressId != "") {
        url = this.personAddressEditActionUrl.replace("PERSONADDRESSID_TOBEREPLACED", this.addressOverlayPersonAddressId);
    } else {
        url = this.personAddressNewActionUrl;
    }

    var that =  this;
    var datastring = $("form[name='" + this.formName + "']").serialize();
    $.ajax({
        type: "POST",
        url: url,
        data: datastring,
        success: function(data) {
            if (data == "success") {
                location.reload();
            } else {
                $(that.addressOverlayContainer).html(data);
                $(that.addressOverlayLoadingIndicator).hide();
                $(that.addressOverlayContainer).show();
            }
        }
    });

    //Show the loading indicator after the post has been send
    $(this.addressOverlayLoadingIndicator).show();
    $(this.addressOverlayContainer).hide();
};

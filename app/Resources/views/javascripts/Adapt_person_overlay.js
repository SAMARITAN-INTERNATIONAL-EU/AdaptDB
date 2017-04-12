/**
 *
 * @namespace Adapt_person_overlay
 */

/**
 * Functions related to the edit-Person overlay on the person-show page
 * @memberof Adapt_person_overlay
 */
function Adapt_person_overlay() {

    this.formName = "person";
    this.personEditActionUrl = "{{ path('person_edit', {personId: person.id}) }}";

    this.personOverlayLoadingIndicator = $('#editPersonOverlay').find('.loadingIndicator')[0];
    this.personOverlayContainer = $('#editPersonContainer');
}

var adapt_person_overlay = new Adapt_person_overlay();

/**
 * Function to show the overlay
 * @function showEditPersonOverlay
 * @memberof Adapt_person_overlay
 */
Adapt_person_overlay.prototype.showEditPersonOverlay = function() {
    $('#editPersonOverlay').modal('show');

    $(this.personOverlayLoadingIndicator).show();
    $(this.personOverlayContainer).hide();

    var that = this;
    $.get( this.personEditActionUrl, function( data ) {
        $(that.personOverlayContainer).html(data);
        $(that.personOverlayLoadingIndicator).hide();
        $(that.personOverlayContainer).show();
    });
};

/**
 * Submits the form in the Person-Overlay
 * @function personOverlaySubmit
 * @memberof Adapt_person_overlay
 */
Adapt_person_overlay.prototype.personOverlaySubmit = function(event) {
    event.preventDefault();

    var that =  this;
    var datastring = $("form[name='" + this.formName + "']").serialize();
    $.ajax({
        type: "POST",
        url: this.personEditActionUrl,
        data: datastring,
        success: function(data) {
            if (data == "success") {
                location.reload();
            } else {
                $(that.personOverlayContainer).html(data);
                $(that.personOverlayLoadingIndicator).hide();
                $(that.personOverlayContainer).show();
            }
        }
    });

    //Show the loading indicator after the post has been send
    $(this.personOverlayLoadingIndicator).show();
    $(this.personOverlayContainer).hide();
};

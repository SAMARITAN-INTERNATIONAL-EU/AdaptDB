/**
 * Functions for the "Person-Addresses-Overview"
 * @namespace Adapt_personAddress_index
 */

/**
 * Extensions for the "People-Addresses Overview"-page
 * @memberof Adapt_personAddress_index
 */

Adapt_personAddress_index = function() {

    this.grSmIndicatorAge = $('#grSmIndicatorAge');
    this.queryAgeGrSm = $('#people_addresses_filter_queryAgeGrSm');
    this.queryAge = $('#people_addresses_filter_queryAge');
    this.personDeleteActionUrl = "{{ path('person_delete', {'personId' : 'PERSONID_TOBEREPLACED' }) }}";

};

var adapt_personAddress_index = new Adapt_personAddress_index();

/**
 * This function changes currentPage value in the form to open that page on the next page-load
 * @function goToPage
 * @memberof Adapt_personAddress_index
 * @param int targetPageString
 */
Adapt_personAddress_index.prototype.goToPage = function(targetPageString) {

    $('#people_addresses_filter_currentPage').val(targetPageString);
    $('form[name="people_addresses_filter"]').submit();

};

/**
 * Show a confirmation dialog before deleting a Person
 * @memberof Adapt_personAddress_index
 */
Adapt_personAddress_index.prototype.showDeleteConfirmationForPerson = function(personId, personDescriptionString) {
    var answer1 = confirm('Do you really want to delete Person "' + decodeURIComponent(personDescriptionString) + '"? Warning this will delete all related data for this person including Data Change History, Contact Persons and Addresses. You cannot undo this action!');
    if (answer1 == true) {

        var answer2 = confirm('Please confirm to remove the person. This action cannot be undone!');
        if (answer2 == true) {

            var url = this.personDeleteActionUrl.replace("PERSONID_TOBEREPLACED", personId);


            $.post( url, function( data ) {
                if (data == "success") {
                    location.reload();
                } else {
                    alert('An error occurred while deleting the Person.');
                }
            });
        }
    }
};

/**
 *
 * The function checks if the page-number input was valid - if it was valid the page is reloaded
 * @function goToPageButtonClicked
 * @memberof Adapt_personAddress_index
 * @param {event} event
 */
Adapt_personAddress_index.prototype.goToPageButtonClicked = function(event) {

    event.preventDefault();

    var page = parseInt($('#goto_page_input').val());

    var pagesTotal = parseInt("{{ pagesTotal }}");
    if (page < 1 || page > pagesTotal) {
        alert("Please enter a page between 1 and " + pagesTotal + ".");
    } else {
        $('#people_addresses_filter_currentPage').val(page);
        $('form[name="people_addresses_filter"]').submit();
    }
};


/**
 * Sets the GrSm (Greater than / Smaller than)-indicator based on the value of the form field
 * @memberof Adapt_personAddress_index
 */
Adapt_personAddress_index.prototype.updateAgeGrSmIndicator = function () {

    if ($("#people_addresses_filter_queryAgeGrSm").val() == "smaller") {
        $(this.grSmIndicatorAge).text("≤");
        $(this.grSmIndicatorAge).attr("title", "less than");
    } else {
        $(this.grSmIndicatorAge).text("≥");
        $(this.grSmIndicatorAge).attr("title", "greater than");
    }
};



/**
 * Triggers and reset of the search-fields.
 * @memberof Adapt_personAddress_index
 * @param {event} event
 */
Adapt_personAddress_index.prototype.resetSearchFieldButtonClicked = function(event) {
    //prevents the form data to be send
    event.preventDefault();
    $('#people_addresses_filter_queryIsActive').val("");
    $('#people_addresses_filter_querySafetyStatus').val("");
    $('#people_addresses_filter_queryFiscalCode').val("");
    $('#people_addresses_filter_queryFirstName').val("");
    $('#people_addresses_filter_queryLastName').val("");
    $('#people_addresses_filter_queryDateOfBirth').val("");
    $('#people_addresses_filter_queryStreetName').val("");
    $('#people_addresses_filter_queryStreetNr').val("");
    $('#people_addresses_filter_queryZipcode').val("");
    $('#people_addresses_filter_queryCity').val("");

    //set currentPage to 1
    $('#people_addresses_filter_currentPage').val(1);

    this.submitFilterForm();

};

/**
 * Sets currentPage to 1 and submits the filter-form
 * @memberof Adapt_personAddress_index
 * @param {event} event
 */
Adapt_personAddress_index.prototype.applySearchFieldButtonClicked = function(event) {
    //prevents the form data to be send
    event.preventDefault();

    //set currentPage to 1
    $('#people_addresses_filter_currentPage').val(1);

    this.submitFilterForm();
};

/**
 * Submits the filter form
 * @memberof submitFilterForm
 */
Adapt_personAddress_index.prototype.submitFilterForm = function() {
        $('form[name="people_addresses_filter"]').submit();
};

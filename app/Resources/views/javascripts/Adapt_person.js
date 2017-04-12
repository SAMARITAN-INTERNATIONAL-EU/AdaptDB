/**
 * Methods for the "New Person" and the "Show Potential Identity"-pages
 * @namespace Adapt_person
 *
 */

Adapt_person = function() {

    this.addPersonToPotentialIdentityTableBody = $('#AddPersonToPotentialIdentityTable tbody');
};

var adapt_person = new Adapt_person();

/**
 * Function to open the container where a person can be added to a PI
 *
 * @function openAddPersonToPIContainer
 * @memberof Adapt_person
 */
Adapt_person.prototype.openAddPersonToPIContainer = function(e) {
    e.preventDefault();
    if (!e)
        e = window.event;
    var sender = e.srcElement || e.target;
    //disable the button "Add another person to potential idenitiy"
    $(sender).attr('disabled', 'disabled');

    $('#addPersonToPIContainer').slideDown(500);
    $.scrollTo('#addPersonToPIContainer', this.scrollToTransitionMilliseconds);
};

/**
 *
 * @function resetAddPotentialIdentityFilter
 * @memberof Adapt_person
 */
Adapt_person.prototype.resetAddPotentialIdentityFilter = function() {

    $('.addPotentialIdentityFilterField').each(function( index ) {
        $(this).val("");
    });

    that.submitAddPotentialIdentityFilter();
};

/**
 *
 * The function collects all the filter-propertiy and send the query to the controller
 * When the values are returned this function inserts the persons into the table
 *
 * @function submitAddPotentialIdentityFilter
 * @memberof Adapt_person
 */
Adapt_person.prototype.submitAddPotentialIdentityFilter = function() {

    $(this.addPersonToPotentialIdentityTableBody).html("");

    // Collect the filter properties
    // It's important that this is an object and not an array!
    var filterItems = {};
    $('#addPotentialIdentity_queryFiscalCode').val();

    $('.addPotentialIdentityFilterField').each(function( index ) {
        if ($(this).val()) {
            filterItems[$(this).data("filtercolumn")] = $(this).val();
        }
    });

    var that = this;
    filterItems['thisPersonId'] = "{{ person.id }}";

    var addPotentialIdentityFilterUrl = "{{ path('getPersonsForPotentialIdentityFilterJSON') }}";

    $.post(addPotentialIdentityFilterUrl, {'filterArray': JSON.stringify(filterItems)} , function(data, textStatus) {

        if (data.length == 0) {
            $(that.addPersonToPotentialIdentityTableBody).html("<tr><td colspan='7'>No Person Found!</td></tr>")
        } else {

            var items = [];
            $.each( data, function( key, personInLoop ) {

                if (personInLoop.safe == true) {
                    var safeString = "<span class='badge badge-safe personShow-safetyStatus-badge'>Safe</span>";

                } else {
                    var safeString = "<span class='badge badge-notSafe personShow-safetyStatus-badge'>Not Safe</span>";
                }

                if (personInLoop.date_of_birth) {
                    //Dateformat Y-m-d
                    var dateOfBirth =  new Date(personInLoop.date_of_birth);
                    var dateOfBirthString = dateOfBirth.getFullYear() + "-" +
                        dateOfBirth.getMonth() + 1 + "-" + // months are zero indexed
                        dateOfBirth.getDate();

                } else {
                    var dateOfBirthString = "[not set]";
                }

                var actionString = "<a data-personid='" + personInLoop.id + "' onclick='adapt_person.addPersonToPotentialIdentity(event)' title='Add this person to the list of potential identities of {{ person.firstName }} {{ person.lastName }}'><span class='glyphicon glyphicon-plus' aria-hidden='true'></span> Add To PI</a>";

                if (personInLoop.data_source.is_official) {
                    var dataSourceBadgeString = "<span class='badge badge-datasource badge-isOfficial'>" + personInLoop.data_source.name_short + "</span>"
                } else {
                    var dataSourceBadgeString = "<span class='badge badge-datasource'>" + personInLoop.data_source.name_short + "</span>"
                }

                items.push("<tr><td>" + dataSourceBadgeString + "</td><td>" + personInLoop.fiscal_code + "</td><td>" + personInLoop.first_name + "</td><td>" + personInLoop.last_name + "</td><td>" + dateOfBirthString + "</td><td>" + safeString + "</td><td>" + actionString +"</td></td></tr>");

            });

            $(that.addPersonToPotentialIdentityTableBody).html("");
            $(that.addPersonToPotentialIdentityTableBody).append(items.join(""));
        }

    }, "json");
    $(that.addPersonToPotentialIdentityTableBody).html("<tr><td colspan='7'>Please wait...</td></tr>")

};

/**
 * Opens the url to dissolve a PI
 * @function dissolvePotentialIdentityOfPerson
 * @memberof Adapt_person
 */
Adapt_person.prototype.dissolvePotentialIdentityOfPerson = function(event, personId) {

    var answer = confirm("{{ 'dissolvePotentialIdentity_confirmationMessage' | trans }}");

    if (answer == true) {
        var dissolvePotentialIdentityOfPersonUrl = "{{ path('dissolvePotentialIdentityOfPerson', {'personId': 'PERSONID_TOBEREPLACED'}) }}";
        dissolvePotentialIdentityOfPersonUrl = dissolvePotentialIdentityOfPersonUrl.replace("PERSONID_TOBEREPLACED", personId);
        window.location.href = dissolvePotentialIdentityOfPersonUrl;
    }
};


/**
 * Opens the url to remove a person to from a PI
 * @function removePersonFromPotentialIdentity
 * @memberof Adapt_person
 */
Adapt_person.prototype.removePersonFromPotentialIdentity = function(event, personId, originPersonId) {
    event.preventDefault();


    var answer = confirm("{{ 'removePersonFromPotentialIdentity_confirmationMessage' | trans }}");

    if (answer == true) {

        if (personId != "") {
            var removePersonFromPotentialIdentityUrl = "{{path('removePersonFromPotentialIdentity', {'personIdToRemove' : 'PERSONID_TOBEREPLACED', 'originPersonId': 'ORIGINPERSONID_TOBEREPLACED'}) }}";
            removePersonFromPotentialIdentityUrl =  removePersonFromPotentialIdentityUrl.replace("PERSONID_TOBEREPLACED", personId);
            removePersonFromPotentialIdentityUrl =  removePersonFromPotentialIdentityUrl.replace("ORIGINPERSONID_TOBEREPLACED", originPersonId);
            window.location.href = removePersonFromPotentialIdentityUrl;
        }
    }
};


/**
 *
 * Opens the url to add a person to a PI
 *
 * @function addPersonToPotentialIdentity
 * @memberof Adapt_person
 */
Adapt_person.prototype.addPersonToPotentialIdentity = function(e) {

    if (!e) {
        e = window.event;
    }

    var sender = e.srcElement || e.target;

    if ("{{ emergencyId }}" == "") {
        var url = "{{ path('addPersonToPotentialIdentityOfPerson', {'personExistingId' : 'PERSONEXISTING_TOBEREPLACED', 'personNewId' : 'PERSONNEW_TOBEREPLACED'}) }}";
    } else {
        var emergencyId = parseInt("{{ emergencyId }}");
        var url = "{{ path('addPersonToPotentialIdentityOfPerson', {'personExistingId' : 'PERSONEXISTING_TOBEREPLACED', 'personNewId' : 'PERSONNEW_TOBEREPLACED', 'emergencyId' : 'EMERGENCYID_TOBEREPLACED'}) }}";
    }

    var personNewId =  parseInt($(sender).data('personid'));
    var personExistingId = parseInt("{{ person.id}}");

    if (personNewId >= 0 && personExistingId >=0) {
        url = url.replace("PERSONEXISTING_TOBEREPLACED", personExistingId);
        url = url.replace("PERSONNEW_TOBEREPLACED", personNewId);
        url = url.replace("EMERGENCYID_TOBEREPLACED", emergencyId);
        window.location.href = url;
    } else {
        console.log("Error: Can't add Potential Identity, because one of the Ids is not valid.")
    }
};

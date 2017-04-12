/**
 *
 * @namespace Adapt_user
 */

/**
 * @memberof Adapt_user
 */
Adapt_user = function() {};

var adapt_user = new Adapt_user();

/**
 *
 * @memberof Adapt_user
 */
Adapt_user.prototype.checkAtLeastOneRoleSelected = function() {


    $("form[name='user']").on("submit", function(event) {
    //Check if at least one role is set
    var numberOfCheckedRolesCheckboxes = $("input[name='user[roles][]']:checked").length;
    if (numberOfCheckedRolesCheckboxes == 0) {
        event.preventDefault();
        alert("Please select at least one role.");
    }
});


};

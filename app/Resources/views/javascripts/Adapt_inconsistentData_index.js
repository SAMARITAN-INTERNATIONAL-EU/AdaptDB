/**
 * @namespace Adapt_inconsistentData_index
 */

/**
 *
 * @function Adapt_inconsistentData_index
 * @memberof Adapt_inconsistentData_index
 */
function Adapt_inconsistentData_index() {
    this.removeInconsistentPIUrl = "{{path('removeInconsistentPIById', {'id': 'INCONSISTENTPIID_TOBEREPLACED'})  }}";

}

var adapt_inconsistentData_index = new Adapt_inconsistentData_index();

/**
 *
 * @function removeInconsistentPI
 * @param {int} inconsistentPIId
 * @memberof Adapt_inconsistentData_index
 */
Adapt_inconsistentData_index.prototype.removeInconsistentPI = function (inconsistentPIId)
{
    var answer = confirm("Do you really want to remove this entry from the list of inconsistent potential identities?");

    if (answer == true) {
        window.location.href = this.removeInconsistentPIUrl.replace("INCONSISTENTPIID_TOBEREPLACED", inconsistentPIId);
    }
};



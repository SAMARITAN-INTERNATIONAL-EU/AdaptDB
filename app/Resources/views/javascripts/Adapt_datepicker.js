/**
 * To enable the datepicker functionality
 *
 * @namespace Adapt_datepicker
 */

/**
 * Function to create the adapt_datepicker prototype
 * @memberof Adapt_datepicker
 */
Adapt_datepicker = function() {
};

var adapt_datepicker = new Adapt_datepicker();

/**
 *
 *  This initializes the datepicker with specific css-classes
 * @memberof Adapt_datepicker
 */
Adapt_datepicker.prototype.init = function() {

    $('.datepicker.datepickerDateOfBirth').datepicker({
        startView: 3,
        maxViewMode: 3,
        toggleActive: true
    });

    $('.datepicker.datepickerValidUntil').datepicker({
        startView: 2,
        maxViewMode: 2,
        toggleActive: true
    });

    $('.datepicker.datepickerAbsenceFrom').datepicker({
        startView: 1,
        maxViewMode: 2,
        toggleActive: true
    });

    $('.datepicker.datepickerAbsenceTo').datepicker({
        startView: 1,
        maxViewMode: 2,
        toggleActive: true
    });

    $('.datepicker').attr("type", "text");
};

adapt_datepicker.init();
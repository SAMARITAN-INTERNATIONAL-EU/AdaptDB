/**
 * * Function for the Step1-page of the import-wizard
 * @namespace Adapt_import_step2
 */
Adapt_import_step2 = function() {
    this.settingDescriptions = $('.settingDescription');
};

/**
 *  Shows the description divs which are hidden when the page is loaded
 *
 * @function showSettingDescriptions
 * @memberof Adapt_import_step2
 */
Adapt_import_step2.prototype.showSettingDescriptions = function() {

    $(this.settingDescriptions).show(500);

    //Hide the button
    $('#showSettingDescriptionsButton').hide();

};

var adapt_import_step2 = new Adapt_import_step2();

/**
 * Function for the Step1-page of the import-wizard
 * @namespace Adapt_import_step1
 */
Adapt_import_step1 = function() {
    this.openImportsOfDataSourcePageUrl = "{{path('import_importsofdatasource', {'dataSourceId': 'DATASOURCEIDTOBEREPLACED'}) }}";
};

/**
 *
 * @function openImportsOfDataSourcePage
 * @memberof Adapt_import_step1
 */
Adapt_import_step1.prototype.openImportsOfDataSourcePage = function(event) {

    //Prevents that the form is submitted
    event.preventDefault();
    var dataSourceId = $("#import_step1_dataSource option:selected").val();
    var tmpUrl = this.openImportsOfDataSourcePageUrl.replace("DATASOURCEIDTOBEREPLACED", dataSourceId);
    window.location = tmpUrl;

};

var adapt_import_step1 = new Adapt_import_step1();

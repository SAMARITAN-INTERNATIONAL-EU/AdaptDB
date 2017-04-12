/**
 * @namespace Adapt_deleteModalInitializer
 */

/**
 * This function adds a click handler to the delete buttons. When a delete button is clicked an confirmation dialog is
 * shown to get the users confirmation to delete the entitiy.
 * @memberof Adapt_deleteModalInitializer
 */
function Adapt_deleteModalInitializer(entityName, nameColumnInt) {
    this.entityName = entityName;
    this.nameColumnInt = nameColumnInt;
}

/**
 * This is used to add confirm-dialogs to delete buttons on the overview-pages.
 * @memberof Adapt_deleteModalInitializer
 */
Adapt_deleteModalInitializer.prototype.showModal = function(identifierString, callback) {

    console.log("Adapt_deleteModalInitializer");
    if (identifierString == "") {
        var answer = confirm("Do you want to delete this " + this.entityName + "?");
    } else {
        var answer = confirm("Do you want to delete " + this.entityName + " with name '" + decodeURIComponent(identifierString) + "'?");
    }

    if (answer == true) {
        callback();
    }
};

/**
 * The function prevents the page to be switched. After that the name of the entity is fetched to show it on the
 * confirm message. After that the original "destination-page" is opened.
 * @memberof Adapt_deleteModalInitializer
 */
Adapt_deleteModalInitializer.prototype.init = function() {

    var that = this;
    $('.deleteEntityButton').click(function(e) {
        e.preventDefault();

        var identifierString = "";

        if (that.nameColumnInt != "") {

            if ($($(this).parent().parent()[0]).prop('nodeName') != "UL") {
                var identifierString = $($(this).parent().parent().children()[that.nameColumnInt]).html();
            } else {
                //Allows to make this work on Emergency-index where the delete-button is inside an ul-li-list.
                var identifierString = $($(this).parent().parent().parent().parent().children()[that.nameColumnInt]).html();
            }

        }

        // Stores the href to be able to change to this site after
        var tmpHref = this.href;

        that.showModal(identifierString, function() {
            // Continues with the page-change
            window.location.href = tmpHref;

        });
    });
};

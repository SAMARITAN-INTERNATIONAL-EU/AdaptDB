/**
 *
 * @namespace Adapt_searchInfoModal
 */

Adapt_searchInfoModal = function() {};

var adapt_searchInfoModal = new Adapt_searchInfoModal();

/**
 * This function triggers the SearchInfoModal to show
 * @function openSearchInfoModal
 * @memberof Adapt_searchInfoModal
 */
Adapt_searchInfoModal.prototype.openSearchInfoModal = function(event) {
    event.preventDefault();
    $('#searchInfoModal').modal('show');
};

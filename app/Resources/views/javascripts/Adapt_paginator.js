/**
 * Pagination related Methods
 * @namespace Adapt_paginator
 */

/**
 * For paginator related functions
 * @memberof Adapt_paginator
 * @param string filterFormName Name of the filter-Form
 */

Adapt_paginator = function(filterFormName) {
    this.filterFormName = filterFormName;
};

adapt_paginator = new Adapt_paginator();

/**
 *
 * The function checks if the page-number input was valid - if it was valid the page is reloaded
 * @function goToPageButtonClicked
 * @memberof Adapt_paginator
 * @param {event} event
 */
Adapt_paginator.prototype.goToPageButtonClicked = function(event) {

    event.preventDefault();

    var page = parseInt($('#goto_page_input').val());

    var pagesTotal = parseInt("{{ pagesTotal }}");
    if (page < 1 || page > pagesTotal) {
        alert("Please enter a page between 1 and " + pagesTotal + ".");
    } else {
        $('#' + this.filterFormName + '_currentPage').val(page);
        $('form[name="' + this.filterFormName + '"]').submit();
    }
};


/**
 * This function changes currentPage value in the form to open that page on the next page-load
 * @function goToPage
 * @memberof Adapt_paginator
 * @param int targetPageString
 */
Adapt_paginator.prototype.goToPage = function(targetPageString) {

    $('#' + this.filterFormName + '_currentPage').val(targetPageString);
    $('form[name="' + this.filterFormName + '"]').submit();

};

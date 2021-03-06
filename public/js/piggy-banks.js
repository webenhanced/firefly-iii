/* globals $, lineChart, token, piggyBankID */

// Return a helper with preserved width of cells
var fixHelper = function (e, ui) {
    "use strict";
    ui.children().each(function () {
        $(this).width($(this).width());
    });
    return ui;
};

$(function () {
    "use strict";
    $('.addMoney').on('click', addMoney);
    $('.removeMoney').on('click', removeMoney);

    if (typeof(lineChart) === 'function' && typeof(piggyBankID) !== 'undefined') {
        lineChart('chart/piggy-bank/' + piggyBankID, 'piggy-bank-history');
    }

    $('#sortable tbody').sortable(
        {
            helper: fixHelper,
            stop: stopSorting,
            handle: '.handle',
            start: function (event, ui) {
                // Build a placeholder cell that spans all the cells in the row
                var cellCount = 0;
                $('td, th', ui.helper).each(function () {
                    // For each TD or TH try and get it's colspan attribute, and add that or 1 to the total
                    var colspan = 1;
                    var colspanAttr = $(this).attr('colspan');
                    if (colspanAttr > 1) {
                        colspan = colspanAttr;
                    }
                    cellCount += colspan;
                });

                // Add the placeholder UI - note that this is the item's content, so TD rather than TR
                ui.placeholder.html('<td colspan="' + cellCount + '">&nbsp;</td>');
            }
        }
    );
});


function addMoney(e) {
    "use strict";
    var pigID = parseInt($(e.target).data('id'));
    $('#defaultModal').empty().load('piggy-banks/add/' + pigID, function () {
        $('#defaultModal').modal('show');
    });

    return false;
}

function removeMoney(e) {
    "use strict";
    var pigID = parseInt($(e.target).data('id'));
    $('#defaultModal').empty().load('piggy-banks/remove/' + pigID, function () {
        $('#defaultModal').modal('show');
    });

    return false;
}
function stopSorting() {
    "use strict";
    $('.loadSpin').addClass('fa fa-refresh fa-spin');
    var order = [];
    $.each($('#sortable>tbody>tr'), function (i, v) {
        var holder = $(v);
        var id = holder.data('id');
        order.push(id);
    });
    $.post('/piggy-banks/sort', {_token: token, order: order}).success(function () {
        $('.loadSpin').removeClass('fa fa-refresh fa-spin');
    });
}
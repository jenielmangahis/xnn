$('#show-hide-summary').click(function () {
    $(this).text(function (i, old) {
        return old == 'Show Summary' ? 'Hide Summary' : 'Show Summary';
    });
});
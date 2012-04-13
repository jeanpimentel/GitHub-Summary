(function($){
    $('a.user').tooltip();
    $('.more-info').popover({
        placement: 'left'
    });
    $('#interval').change(function() {
        window.location = '/' + $(this).attr('rel').replace('_', '/') + '/' + $(this).attr('value');
    });
})(jQuery);
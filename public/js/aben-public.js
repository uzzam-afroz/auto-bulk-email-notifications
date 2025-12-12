(function( $ ) {
    'use strict';

    $(".unsubscribe-message").on('click', function (){
        // Hide the message
        $(".unsubscribe-message").hide();
        // Remove query parameters from the URL without reloading the page
        var url = window.location.href.split('?')[0]; // Get the URL without the query string
        window.history.replaceState(null, null, url); // Replace the current URL
    });

})( jQuery );

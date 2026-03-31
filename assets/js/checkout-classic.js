( function ( $ ) {
	'use strict';

	$( function () {
		$( document.body ).on( 'change', '.woompesa-phone-input', function () {
			var cleaned = String( $( this ).val() || '' ).replace( /[^\d+]/g, '' );
			$( this ).val( cleaned );
		} );
	} );
}( jQuery ) );

(function( $ ){
	$( document ).ready( function() {
		/* 
		 *add notice about changing in the settings page 
		 */
		$( '#swpsmtp-mail input' ).bind( "change select", function() {
			if ( $( this ).attr( 'type' ) != 'submit' ) {
				$( '.updated.fade' ).css( 'display', 'none' );
				$( '#swpsmtp-settings-notice' ).css( 'display', 'block' );
			};
		});
	});
})(jQuery);

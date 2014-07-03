;jQuery( function( $ ) {

	/**
	 * Update the description and title character counts displayed to the user.
	 */
	function updateCharacterCounts() {
		$( ['title', 'description'] ).each( function() {
			var input;
			if ( ( input = $( '#wp_seo_meta_' + this ) ).length > 0 ) {
				$( '.' + this + '-character-count' ).html( input.val().length );
			}
		});
	}

	$( '#wp_seo_settings .nav-tab' ).click( function( event ) {
		event.preventDefault();
		$( $( '.nav-tab-active' ).removeClass( 'nav-tab-active' ).attr( 'href' ) ).hide();
		$( $(this).addClass( 'nav-tab-active' ).attr( 'href' ) ).fadeIn( 'fast' );
	} );
	$( '.wp-seo-tab' ).hide();
	$( $( '.nav-tab-active' ).attr( 'href' ) ).show();

	updateCharacterCounts();
	$( '.wp-seo-meta-fields' ).find( 'input, textarea' ).keyup( updateCharacterCounts );
} );
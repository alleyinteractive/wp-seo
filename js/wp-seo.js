;jQuery( function( $ ) {

	/**
	 * Update the description character count displayed to the user.
	 *
	 * @return {Void}
	 */
	function updateDescriptionCharacterCount() {
		$( '.description-character-count' ).html( $( '#wp_seo_meta_description' ).val().length );
	}

	$( '#wp_seo_settings .nav-tab' ).click( function( event ) {
		event.preventDefault();
		$( $( '.nav-tab-active' ).removeClass( 'nav-tab-active' ).attr( 'href' ) ).hide();
		$( $(this).addClass( 'nav-tab-active' ).attr( 'href' ) ).fadeIn( 'fast' );
	} );
	$( '.wp-seo-tab' ).hide();
	$( $( '.nav-tab-active' ).attr( 'href' ) ).show();

	updateDescriptionCharacterCount();
	$( '#wp_seo_meta_description' ).keyup( function() {
		updateDescriptionCharacterCount();
	} );
} );
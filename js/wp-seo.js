;jQuery( function( $ ) {
	$( '#wp_seo_settings .nav-tab' ).click( function( event ) {
		event.preventDefault();
		$( $( '.nav-tab-active' ).removeClass( 'nav-tab-active' ).attr( 'href' ) ).hide();
		$( $(this).addClass( 'nav-tab-active' ).attr( 'href' ) ).fadeIn( 'fast' );
	} );
	$( '.wp-seo-tab' ).hide();
	$( $( '.nav-tab-active' ).attr( 'href' ) ).show();
} );
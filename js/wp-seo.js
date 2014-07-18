;jQuery( function( $ ) {

	/**
	 * Get a link to an "Add another repeatable group" link.
	 *
	 * @return {String}
	 */
	function wpseo_add_more_button() {
		return $( '<a href="#" class="button-secondary wp-seo-add">' + wp_seo_admin.repeatable_add_more_label + '</a>' );
	}

	/**
	 * Toggle the display of the "Remove group" links for a group of nodes.
	 *
	 * @param  {Object} $parent The .node parent
	 */
	function wpseo_toggle_removes( $parent ) {
		$( '.wp-seo-delete', $parent ).toggle( $parent.children().length > 1 );
	}

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

	updateCharacterCounts();
	$( '.wp-seo-post-meta-fields, .wp-seo-term-meta-fields' ).find( 'input, textarea' ).keyup( updateCharacterCounts );

	/**
	 * Add a "Remove" link to groups.
	 *
	 * Appended here to easily use the same localized field label.
	 */
	$( '.wp-seo-repeatable-group' ).append( '<a href="#" class="wp-seo-delete">' + wp_seo_admin.repeatable_remove_label + '</a>' );

	$( '.wp-seo-repeatable' )
		// Append the "Add More" button to each repeatable field.
		.append( wpseo_add_more_button() )
		// Toggle the "Remove" link from each group as needed.
		.each( function( i, el ) {
			wpseo_toggle_removes( $( el ).find( '> .nodes' ) );
	} );

	/**
	 * Add a repeatable group on click.
	 */
	$( '#wp_seo_settings' ).on( 'click', '.wp-seo-add', function( e ) {
		e.preventDefault();
		var $tpl = $( this ).siblings( '.wp-seo-template' );
		var html = _.template( $tpl.html(), { i: $tpl.data( 'start' ) } );
		$tpl.data( 'start', $tpl.data( 'start' ) + 1 );
		$( this ).siblings( '.nodes' ).append( html );
		wpseo_toggle_removes( $( this ).siblings( '.nodes' ) );
	} );

	/**
	 * Remove a repeatable group on click.
	 */
	$( '#wp_seo_settings' ).on( 'click', '.wp-seo-delete', function( e ) {
		e.preventDefault();
		$( this ).parent().hide( 'fast', function(){
			$parent = $( this ).parent();
			$( this ).remove();
			wpseo_toggle_removes( $parent );
		} );
	} );

} );
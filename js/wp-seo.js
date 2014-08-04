;jQuery( function( $ ) {

	/**
	 * Get a link to an "Add another repeatable group" link.
	 *
	 * @return {String}
	 */
	function wpseo_add_more_button() {
		return $( '<a href="#" class="button-secondary wp-seo-add" />' ).text( wp_seo_admin.repeatable_add_more_label );
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
	 *
	 * If a string has a formatting tag, uses Underscores to render a template
	 * with an estimated character count. The estimate is the total string
	 * length minus the combined length of the formatting tags.
	 */
	function wpseo_update_character_counts() {
		_.each( ['title', 'description'], function( field ) {
			var input = $( '#wp_seo_meta_' + field );
			if ( input.length > 0 ) {
				var matches = input.val().match( wp_seo_admin.formatting_tag_pattern );
				var element = '.' + field + '-character-count';
				if ( matches ) {
					$( element ).html( _.template( wp_seo_admin.estimated_count_text, { count: ( input.val().length - matches.join('').length ) } ) );
				} else {
					$( element ).html( input.val().length );
				}
			}
		});
	}

	/**
	 * Add the default formatting tag pattern to the localized object.
	 *
	 * @type {RegExp}
	 */
	window.wp_seo_admin.formatting_tag_pattern = /#[a-zA-Z\_]+#/g;

	wpseo_update_character_counts();
	$( '.wp-seo-post-meta-fields, .wp-seo-term-meta-fields' ).find( 'input, textarea' ).keyup( wpseo_update_character_counts );
	// Update the character counts after a term is added via AJAX.
	$( document ).ajaxComplete( function() {
		if ( $( '#addtag' ).length > 0 ) {
			wpseo_update_character_counts();
		}
	} );

	/**
	 * Add a "Remove" link to groups.
	 *
	 * Appended here to easily use the same localized field label.
	 */
	$( '.wp-seo-repeatable-group' ).append( $( '<a href="#" class="wp-seo-delete" />' ).text( wp_seo_admin.repeatable_remove_label ) );

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
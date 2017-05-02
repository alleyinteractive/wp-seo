;jQuery( function( $ ) {
	var $document, addtag, frame, metabox;

	$document = $( document );
	addtag    = document.getElementById( 'addtag' );
	images    = $( '.wp-seo-image-container' );

	/**
	 * Get a link to an "Add another repeatable group" link.
	 *
	 * @return {String}
	 */
	function wp_seo_add_more_button() {
		return $( '<a href="#" class="button-secondary wp-seo-add" />' ).text( wp_seo_admin.repeatable_add_more_label );
	}

	/**
	 * Toggle the display of the "Remove group" links for a group of nodes.
	 *
	 * @param  {Object} $parent The .node parent
	 */
	function wp_seo_toggle_removes( $parent ) {
		$( '.wp-seo-delete', $parent ).toggle( $parent.children().length > 1 );
	}

	/**
	 * Initialize character counting for an input.
	 *
	 * @param  {Element} input           Element whose value should be counted.
	 * @param  {Element} character_count Element that displays the count.
	 */
	function init_character_count( input, character_count ) {
		var update_count, debounce_update_count;

		/**
		 * Update the character count displayed to the user.
		 *
		 * Fetches the count from the server-side calculator if necessary.
		 */
		update_count = function () {
			if ( ! input.value.length ) {
				character_count.textContent = input.value.length;
				return;
			}

			$.get(
				wp_seo_admin.ajaxurl,
				{
					action: 'wp_seo_display_character_count',
					string: input.value,
				},
				function ( response ) {
					if ( response.success ) {
						character_count.textContent = response.data;
					} else {
						character_count.textContent = wp_seo_admin.l10n.character_count_calculator_missing;
					}
				}
			);
		};

		// Debounced version of the above.
		debounce_update_count = _.debounce( update_count, 500 );

		// Update the count (eventually) after typing.
		input.addEventListener( 'keyup', function () {
			character_count.textContent = wp_seo_admin.l10n.calculating_character_count;
			debounce_update_count();
		});

		if ( addtag ) {
			/**
			 * Update the count after a term is added via AJAX.
			 *
			 * This doesn't create an infinite loop because update_count()
			 * doesn't make an Ajax request when the passed input has no value.
			 */
			$document.ajaxComplete(function () {
				if ( ! input.value.length ) {
					update_count();
				}
			});
		}

		// Update the count now.
		update_count();
	}

	/**
	 * Init character counts for eligible inputs.
	 */
	$( '.wp-seo-post-meta-fields, .wp-seo-term-meta-fields' )
		.find( 'input, textarea' )
		.each(function ( index, input ) {
			if ( -1 === input.id.indexOf( 'wp_seo_meta_' ) ) {
				return;
			}

			character_count = document.querySelector( '.' + input.id.replace( 'wp_seo_meta_', '' ) + '-character-count' );

			if ( ! character_count ) {
				return;
			}

			init_character_count( input, character_count );
		});

	/**
	 * Add a "Remove" link to groups.
	 *
	 * Appended here to easily use the same localized field label.
	 */
	$( '.wp-seo-repeatable-group' ).append( $( '<a href="#" class="wp-seo-delete" />' ).text( wp_seo_admin.repeatable_remove_label ) );

	$( '.wp-seo-repeatable' )
		// Append the "Add More" button to each repeatable field.
		.append( wp_seo_add_more_button() )
		// Toggle the "Remove" link from each group as needed.
		.each( function( i, el ) {
			wp_seo_toggle_removes(
				$( el ).find( '> .nodes' )
			);
		}
	);

	/**
	 * Add a repeatable group on click.
	 */
	$( '#wp_seo_settings' ).on( 'click', '.wp-seo-add', function( e ) {
		e.preventDefault();
		var $tpl = $( this ).siblings( '.wp-seo-template' );
		var html = _.template( $tpl.html() );
		$tpl.data( 'start', $tpl.data( 'start' ) + 1 );
		$( this ).siblings( '.nodes' ).append( html( { i: $tpl.data( 'start' ) } ) );
		wp_seo_toggle_removes( $( this ).siblings( '.nodes' ) );
	} );

	/**
	 * Remove a repeatable group on click.
	 */
	$( '#wp_seo_settings' ).on( 'click', '.wp-seo-delete', function( e ) {
		e.preventDefault();
		$( this ).parent().hide( 'fast', function(){
			$parent = $( this ).parent();
			$( this ).remove();
			wp_seo_toggle_removes( $parent );
		} );
	} );

	if ( images ) {
		images.each(function ( index, image ) {
			var imageblock = $( image );
			imageblock
				.find( '.delete-custom-img' )
				.on( 'click', function ( evt ) {
					var thisImage = $( this ).parents( ':eq(1)' );
					evt.preventDefault();
					thisImage.find( '.custom-img-container' ).html( '' );
					thisImage.find( '.upload-custom-img' ).removeClass( 'hidden' );
					thisImage.find( '.delete-custom-img' ).addClass( 'hidden' );
					thisImage.find( '.custom-img-id' ).val( '' );
				});
			imageblock
				.find( '.upload-custom-img' )
				.on( 'click', function ( evt ) {
					var thisImage = $( this ).parents( ':eq(1)' );
	 				evt.preventDefault();
					frame = wp.media({
						element: thisImage,
						title: wp_seo_admin.l10n.choose_an_image,
						button: {
							text: wp_seo_admin.l10n.select_image
						},
						multiple: false, // Set to true to allow multiple files to be selected
						library: { type: 'image' },
					});
					frame.on( 'select', function() {
						var attachment = frame.state().get( 'selection' ).first().toJSON();
						var img = document.createElement( 'IMG' );
						if ( attachment.sizes.thumbnail.url ) {
							img.src = attachment.sizes.thumbnail.url;
						} else {
							img.src = attachment.url;
						}
						img.alt = attachment.alt;
						frame.options.element[0].firstChild.append( img );
						frame.options.element.find( '.custom-img-id' ).val( attachment.id );
						frame.options.element.find( '.upload-custom-img' ).addClass( 'hidden' );
						frame.options.element.find( '.delete-custom-img' ).removeClass( 'hidden' );
					});
					frame.open();
				});
		});
	}
} );

(function ( $ ) {
	/**
	 * Append an "Add Another" button to an element.
	 *
	 * @param {Element} el The element.
	 */
	var appendAddRepeatableButton = function ( el ) {
		var $repeatable = $( el );

		if ( $repeatable.find( '.wp-seo-add-repeatable' ).length ) {
			return;
		}

		$repeatable.append(
			$( '<p />' ).append(
				$( '<button />' )
					.addClass( 'button-secondary wp-seo-add-repeatable' )
					.text( wpSeo.l10n.addAnother )
					.on( 'click', onClickAddRepeatable )
				)
		);
	};

	/**
	 * Append a "Remove" button to a repeatable element.
	 *
	 * @param {Element} el The element.
	 */
	var appendRemoveRepeatedButton = function ( el ) {
		var $repeated = $( el );

		if ( $repeated.find( '.wp-seo-remove-repeated' ).length ) {
			return;
		}

		if ( ! $repeated.hasClass( 'wp-seo-repeated' ) ) {
			return;
		}

		$repeated.append(
			$( '<p />' ).append(
				$( '<button />' )
					.addClass( 'wp-seo-remove-repeated' )
					.text( wpSeo.l10n.remove )
					.on( 'click', onClickRemoveRepeatable )
				)
		);
	};

	/**
	 * Update all the character counts on the page.
	 */
	var updateAllCharacterCounts = function () {
		$( '.wp-seo-has-character-count' ).each(function ( i, el ) {
			 updateCharacterCount( el );
		});
	};

	/**
	 * Update the character count of a given element.
	 *
	 * The element should have a corresponding element whose 'data-character-
	 * count-for' attribute is the element ID.
	 *
	 * @param {Element} el The element whose characters should be counted.
	 */
	var updateCharacterCount = function ( el ) {
		var $update;

		if ( ! el.id ) {
			return;
		}

		$update = $( '[data-character-count-for=' + el.id + ']' );

		if ( ! $update.length ) {
			return;
		}

		if ( typeof el.value !== 'string' ) {
			return;
		}

		$update.text( el.value.length );
	};

	/**
	 * Fires after the 'keyup' event is triggered on an element with a character count.
	 *
	 * @param {Event} e
	 */
	var onKeyupHasCharacterCount = function ( e ) {
		updateCharacterCount( e.target );
	};

	/**
	 * Fires after clicking a repeatable field's "Add Another" button.
	 *
	 * @param {Event} e
	 */
	var onClickAddRepeatable = function ( e ) {
		var $target = $( e.target );
		var $template = $target
			.closest( '.wp-seo-repeatable' )
			.find( '.wp-seo-template' );

		if ( ! $template.length ) {
			return;
		}

		// Use parent() to escape from the <p> around the button.
		$target.parent().before( _.template( $template.html() ) );

		appendRemoveRepeatedButton(
			$target
			.closest( '.wp-seo-repeatable' )
			.find( '.wp-seo-repeated' )
			.last()
		);

		$template.trigger( 'add' );
	};

	/**
	 * Fires after clicking a repeated field's "Remove" button.
	 *
	 * @param {Event} e
	 */
	var onClickRemoveRepeatable = function ( e ) {
		var $target = $( e.target );

		$target.closest( '.wp-seo-repeated' ).hide( 'fast', function () {
			this.remove();
		});
	};

	/**
	 * Fires after a repeated field is added to the Arbitrary Tag repeatable.
	 */
	var onAddArbitraryTag = function () {
		$( this )
			.siblings( '.wp-seo-repeated' )
			.last()
			.find( 'label' )
			.each(function ( i, label ) {
				var $label = $( label );
				var id = _.uniqueId( 'wp-seo-' );

				$label.attr( 'for', id );
				$label.next( 'input' ).attr( 'id', id );
			});
	};

	$( document ).on( 'ready', function () {
		$( '.wp-seo-repeatable' ).each(function ( i, repeatable ) {
			appendAddRepeatableButton( repeatable );
		});

		$( '.wp-seo-repeated' ).each(function ( i, repeated ) {
			appendRemoveRepeatedButton( repeated );
		});

		updateAllCharacterCounts();
		$( '.wp-seo-has-character-count' ).each(function ( i, el ) {
			$( el ).on( 'keyup', onKeyupHasCharacterCount );
		});

		$( '#wp-seo-arbitrary-tags-template' ).on( 'add', onAddArbitraryTag );
	});

	$( document ).on(' ajaxComplete', function () {
		if ( $( '#addtag' ).length > 0 ) {
			updateAllCharacterCounts();
		}
	} );
})( jQuery );

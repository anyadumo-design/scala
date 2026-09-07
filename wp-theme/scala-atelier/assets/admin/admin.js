/* global jQuery, wp, SCALA_ADMIN */
/**
 * Адмінка теми SCALA: вибір зображень і повторювані блоки.
 */
( function ( $ ) {
	'use strict';

	/* ------------------------------------------------------------------
	   Вибір зображення з медіабібліотеки
	   ------------------------------------------------------------------ */
	$( document ).on( 'click', '[data-scala-image-pick]', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '[data-scala-image]' );

		var frame = wp.media( {
			title: SCALA_ADMIN.chooseImage,
			button: { text: SCALA_ADMIN.useImage },
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var thumb = attachment.sizes && attachment.sizes.medium
				? attachment.sizes.medium.url
				: attachment.url;

			$wrap.find( '[data-scala-image-input]' ).val( attachment.id );
			$wrap.find( '.scala-image__preview' ).html( $( '<img>' ).attr( { src: thumb, alt: '' } ) );
			$wrap.find( '[data-scala-image-clear]' ).prop( 'hidden', false );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '[data-scala-image-clear]', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '[data-scala-image]' );

		$wrap.find( '[data-scala-image-input]' ).val( 0 );
		$wrap.find( '.scala-image__preview' ).html(
			$( '<span class="scala-image__empty"></span>' ).text( '—' )
		);
		$( this ).prop( 'hidden', true );
	} );

	/* ------------------------------------------------------------------
	   Повторювані блоки
	   ------------------------------------------------------------------ */

	/**
	 * Перенумеровує рядки: і підписи, і атрибути name.
	 * Без цього після видалення рядка індекси в name лишаються з
	 * дірками, і PHP отримує розріджений масив.
	 */
	function reindex( $rep ) {
		$rep.find( '[data-scala-rep-rows]' ).first().children( '[data-scala-rep-row]' ).each( function ( i ) {
			var $row = $( this );

			$row.find( '.scala-rep__num' ).first().text( i + 1 );

			$row.find( 'input, select, textarea' ).each( function () {
				var name = $( this ).attr( 'name' );

				if ( ! name ) {
					return;
				}

				// Замінюємо лише останній індекс перед іменем поля.
				$( this ).attr(
					'name',
					name.replace( /\[(\d+|__INDEX__)\](\[[^\]]+\])$/, '[' + i + ']$2' )
				);
			} );
		} );
	}

	$( document ).on( 'click', '[data-scala-rep-add]', function ( e ) {
		e.preventDefault();

		var $rep = $( this ).closest( '[data-scala-repeater]' );
		var html = $rep.find( '[data-scala-rep-template]' ).first().html();
		var $rows = $rep.find( '[data-scala-rep-rows]' ).first();

		$rows.append( html.replace( /__INDEX__/g, String( $rows.children().length ) ) );
		reindex( $rep );
	} );

	$( document ).on( 'click', '[data-scala-rep-del]', function ( e ) {
		e.preventDefault();

		if ( ! window.confirm( SCALA_ADMIN.confirmRow ) ) {
			return;
		}

		var $rep = $( this ).closest( '[data-scala-repeater]' );

		$( this ).closest( '[data-scala-rep-row]' ).remove();
		reindex( $rep );
	} );

	$( document ).on( 'click', '[data-scala-rep-up]', function ( e ) {
		e.preventDefault();

		var $row = $( this ).closest( '[data-scala-rep-row]' );
		var $prev = $row.prev( '[data-scala-rep-row]' );

		if ( $prev.length ) {
			$row.insertBefore( $prev );
			reindex( $row.closest( '[data-scala-repeater]' ) );
		}
	} );

	$( document ).on( 'click', '[data-scala-rep-down]', function ( e ) {
		e.preventDefault();

		var $row = $( this ).closest( '[data-scala-rep-row]' );
		var $next = $row.next( '[data-scala-rep-row]' );

		if ( $next.length ) {
			$row.insertAfter( $next );
			reindex( $row.closest( '[data-scala-repeater]' ) );
		}
	} );
} )( jQuery );

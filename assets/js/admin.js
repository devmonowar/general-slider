( function ( $ ) {
	'use strict';

	var L = window.GeneralSliderAdmin || {};
	var counter = Date.now();

	function renumber() {
		$( '#gs-repeater .gs-slide-row' ).each( function ( i ) {
			$( this ).find( '.gs-slide-row__num' ).text( i + 1 );
		} );
	}

	function addSlide() {
		var html = $( '#gs-slide-template' ).html().replace( /__i__/g, 'n' + ( counter++ ) );
		$( '#gs-repeater .gs-repeater__list' ).append( html );
		renumber();
	}

	function removeSlide( $row ) {
		if ( window.confirm( L.confirm || 'Remove this slide?' ) ) {
			$row.remove();
			renumber();
		}
	}

	function chooseImage( $row ) {
		var frame = wp.media( {
			title: L.chooseImage || 'Choose image',
			button: { text: L.useImage || 'Use this image' },
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$row.find( '.gs-image-id' ).val( att.id );
			$row.find( '.gs-slide-row__preview' ).html( '<img class="gs-slide-row__img" src="' + url + '" alt="" />' );
			$row.find( '.gs-remove-image' ).show();
		} );

		frame.open();
	}

	function removeImage( $row ) {
		$row.find( '.gs-image-id' ).val( '' );
		$row.find( '.gs-slide-row__preview' ).empty();
		$row.find( '.gs-remove-image' ).hide();
	}

	function chooseVideo( $row ) {
		var frame = wp.media( {
			title: L.chooseVideo || 'Choose video',
			button: { text: L.useVideo || 'Use this video' },
			library: { type: 'video' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$row.find( '.gs-video-url' ).val( att.url );
		} );

		frame.open();
	}

	$( function () {
		$( '#gs-add-slide' ).on( 'click', addSlide );

		$( '#gs-repeater' ).on( 'click', '.gs-remove-slide', function () {
			removeSlide( $( this ).closest( '.gs-slide-row' ) );
		} );

		$( '#gs-repeater' ).on( 'click', '.gs-choose-image', function () {
			chooseImage( $( this ).closest( '.gs-slide-row' ) );
		} );

		$( '#gs-repeater' ).on( 'click', '.gs-remove-image', function () {
			removeImage( $( this ).closest( '.gs-slide-row' ) );
		} );

		$( '#gs-repeater' ).on( 'click', '.gs-choose-video', function () {
			chooseVideo( $( this ).closest( '.gs-slide-row' ) );
		} );

		if ( $.fn.sortable ) {
			$( '#gs-repeater .gs-repeater__list' ).sortable( {
				handle: '.gs-slide-row__handle',
				placeholder: 'gs-slide-row__placeholder',
				forcePlaceholderSize: true,
				update: renumber
			} );
		}

		renumber();
	} );
} )( jQuery );

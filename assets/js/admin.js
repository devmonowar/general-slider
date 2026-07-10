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

	// ---- Dynamic "slides source" panel ----
	function option( value, label, selected ) {
		var o = document.createElement( 'option' );
		o.value = value;
		o.textContent = label;
		if ( selected ) {
			o.selected = true;
		}
		return o;
	}

	function postTypeEntry( postType ) {
		var data = L.sourceData || {};
		return data[ postType ] || { taxonomies: [] };
	}

	function fillTerms( taxEntry ) {
		var $term = $( '#gs-source-term' );
		var want = String( $term.data( 'selected' ) || '' );
		$term.empty();
		$term.append( option( '0', L.anyTerm || 'All', ! want || '0' === want ) );
		if ( taxEntry && taxEntry.terms ) {
			taxEntry.terms.forEach( function ( t ) {
				$term.append( option( String( t.id ), t.name, want === String( t.id ) ) );
			} );
		}
	}

	function fillTaxonomies() {
		var $tax = $( '#gs-source-taxonomy' );
		var $term = $( '#gs-source-term' );
		if ( ! $tax.length ) {
			return;
		}
		var entry = postTypeEntry( $( '#gs-source-post-type' ).val() );
		var want = String( $tax.data( 'selected' ) || '' );
		$tax.empty();
		$tax.append( option( '', L.anyTerm || 'All', ! want ) );
		( entry.taxonomies || [] ).forEach( function ( tx ) {
			$tax.append( option( tx.tax, tx.label, want === tx.tax ) );
		} );
		var current = $tax.val();
		var match = ( entry.taxonomies || [] ).filter( function ( tx ) { return tx.tax === current; } )[ 0 ];
		fillTerms( match );
		$term.data( 'selected', $term.val() );
	}

	$( function () {
		$( '#gs-add-slide' ).on( 'click', addSlide );

		// Manual / dynamic source toggle.
		$( 'input[name="gs_settings[source][type]"]' ).on( 'change', function () {
			var dynamic = 'dynamic' === $( this ).val() && this.checked;
			$( '#gs-source-panel' ).toggle( dynamic );
			$( '#gs-repeater' ).toggle( ! dynamic );
		} );

		// Cascade: post type -> taxonomies -> terms.
		if ( $( '#gs-source-post-type' ).length ) {
			$( '#gs-source-post-type' ).on( 'change', function () {
				$( '#gs-source-taxonomy' ).data( 'selected', '' );
				$( '#gs-source-term' ).data( 'selected', '' );
				fillTaxonomies();
			} );
			$( '#gs-source-taxonomy' ).on( 'change', function () {
				var entry = postTypeEntry( $( '#gs-source-post-type' ).val() );
				var current = $( this ).val();
				var match = ( entry.taxonomies || [] ).filter( function ( tx ) { return tx.tax === current; } )[ 0 ];
				$( '#gs-source-term' ).data( 'selected', '' );
				fillTerms( match );
			} );
			fillTaxonomies();
		}

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

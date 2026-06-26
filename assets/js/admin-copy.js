( function () {
	'use strict';

	function flash( el ) {
		var hint = el.parentNode.querySelector( '.gs-copied' );
		if ( ! hint ) {
			hint = document.createElement( 'span' );
			hint.className = 'gs-copied';
			el.parentNode.insertBefore( hint, el.nextSibling );
		}
		hint.textContent = el.getAttribute( 'data-copied' ) || 'Copied!';
		hint.classList.add( 'is-visible' );
		window.clearTimeout( hint._t );
		hint._t = window.setTimeout( function () {
			hint.classList.remove( 'is-visible' );
		}, 1500 );
	}

	document.addEventListener( 'click', function ( e ) {
		var el = e.target;
		if ( ! el || ! el.classList || ! el.classList.contains( 'gs-shortcode-copy' ) ) {
			return;
		}
		el.focus();
		el.select();
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( el.value ).then(
				function () { flash( el ); },
				function () { flash( el ); }
			);
		} else {
			try {
				document.execCommand( 'copy' );
			} catch ( err ) {}
			flash( el );
		}
	} );
} )();

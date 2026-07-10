( function () {
	'use strict';

	function initSlider( el ) {
		if ( el.dataset.gsMounted || typeof Splide === 'undefined' ) {
			return;
		}

		var opts = {};
		try {
			opts = JSON.parse( el.getAttribute( 'data-gs' ) || '{}' );
		} catch ( e ) {
			opts = {};
		}

		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var perPage = opts.perPage || 1;

		var splide = new Splide( el, {
			type: opts.type || 'loop',
			rewind: opts.type !== 'loop',
			direction: opts.direction === 'rtl' ? 'rtl' : 'ltr',
			perPage: perPage,
			perMove: 1,
			gap: opts.gap || 0,
			autoplay: reduceMotion ? false : !! opts.autoplay,
			interval: opts.interval || 5000,
			pauseOnHover: true,
			pauseOnFocus: true,
			arrows: opts.arrows !== false,
			pagination: opts.pagination !== false,
			speed: reduceMotion ? 0 : 600,
			drag: true,
			keyboard: 'focused',
			breakpoints: opts.breakpoints || {
				782: { perPage: Math.min( perPage, 2 ) },
				600: { perPage: 1 }
			}
		} );

		// Reflect autoplay state on the play/pause toggle.
		splide.on( 'autoplay:play', function () { el.classList.remove( 'gs-paused' ); } );
		splide.on( 'autoplay:pause', function () { el.classList.add( 'gs-paused' ); } );

		// Entrance animations: restart them whenever the active slide changes.
		if ( el.classList.contains( 'gs-animate' ) && ! reduceMotion ) {
			var animate = function () {
				// In loop mode Splide marks clone slides is-active too; target the
				// real (non-clone) active slide so the visible content animates.
				var active = el.querySelector( '.splide__slide.is-active:not(.splide__slide--clone) .gs-slide__content' );
				if ( active ) {
					active.classList.remove( 'gs-anim' );
					void active.offsetWidth;
					active.classList.add( 'gs-anim' );
				}
			};
			splide.on( 'mounted moved', animate );
		}

		// Thumbnail navigation: sync a second slider if present. Mount the main
		// carousel first, then the thumbnails (Splide's documented order).
		var thumbEl = el.nextElementSibling;
		if ( thumbEl && thumbEl.classList && thumbEl.classList.contains( 'gs-thumbnails' ) ) {
			var thumb = new Splide( thumbEl, {
				fixedWidth: 100,
				fixedHeight: 64,
				gap: 8,
				rewind: true,
				pagination: false,
				arrows: false,
				cover: true,
				isNavigation: true,
				focus: 'center',
				breakpoints: { 600: { fixedWidth: 72, fixedHeight: 46 } }
			} );
			splide.sync( thumb );
			splide.mount();
			thumb.mount();
		} else {
			splide.mount();
		}

		el.dataset.gsMounted = '1';
	}

	function initAll() {
		var nodes = document.querySelectorAll( '.gs-slider' );

		// Without IntersectionObserver, just initialise everything.
		if ( ! ( 'IntersectionObserver' in window ) ) {
			for ( var i = 0; i < nodes.length; i++ ) {
				initSlider( nodes[ i ] );
			}
			return;
		}

		// Otherwise mount each slider only once it nears the viewport.
		var observer = new IntersectionObserver( function ( entries, obs ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					initSlider( entry.target );
					// Stop observing only once it actually mounted, so a slider
					// isn't dropped if Splide wasn't ready on the first pass.
					if ( entry.target.dataset.gsMounted ) {
						obs.unobserve( entry.target );
					}
				}
			} );
		}, { rootMargin: '200px' } );

		for ( var j = 0; j < nodes.length; j++ ) {
			observer.observe( nodes[ j ] );
		}
	}

	if ( 'loading' !== document.readyState ) {
		initAll();
	} else {
		document.addEventListener( 'DOMContentLoaded', initAll );
	}

	// Elementor editor/preview: widgets (re-)render via AJAX after page load,
	// so initialise each one as Elementor announces it ready. initSlider()'s
	// gsMounted guard makes repeat calls harmless. Register immediately when
	// elementorFrontend already exists (our script may load after its init
	// event has fired), otherwise wait for the init event.
	function onElementorElementReady( $scope ) {
		var root = ( $scope && $scope[ 0 ] ) ? $scope[ 0 ] : ( $scope instanceof Element ? $scope : null );
		if ( ! root ) {
			return;
		}
		var nodes = root.querySelectorAll( '.gs-slider' );
		for ( var i = 0; i < nodes.length; i++ ) {
			initSlider( nodes[ i ] );
		}
	}

	function registerElementorHook() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return false;
		}
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/general_slider.default', onElementorElementReady );
		return true;
	}

	if ( ! registerElementorHook() ) {
		window.addEventListener( 'elementor/frontend/init', registerElementorHook );
	}
} )();

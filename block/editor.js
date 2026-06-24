( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ServerSideRender = serverSideRender;

	var data = window.GeneralSliderBlock || { sliders: [] };
	var options = [ { label: __( '— Select a slider —', 'general-slider' ), value: '0' } ];
	( data.sliders || [] ).forEach( function ( s ) {
		options.push( { label: s.title || ( '#' + s.id ), value: String( s.id ) } );
	} );

	blocks.registerBlockType( 'general-slider/slider', {
		edit: function ( props ) {
			var sliderId = props.attributes.sliderId || 0;

			var inspector = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Slider', 'general-slider' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Choose slider', 'general-slider' ),
						value: String( sliderId ),
						options: options,
						onChange: function ( value ) {
							props.setAttributes( { sliderId: parseInt( value, 10 ) || 0 } );
						}
					} )
				)
			);

			var body;
			if ( sliderId ) {
				body = el( ServerSideRender, {
					block: 'general-slider/slider',
					attributes: { sliderId: sliderId }
				} );
			} else {
				body = el(
					'div',
					{ className: 'gs-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-slides' } ),
					el( 'span', {}, __( 'General Slider — choose a slider in the block settings.', 'general-slider' ) )
				);
			}

			return el( Fragment, {}, inspector, body );
		},
		save: function () {
			return null;
		}
	} );
} )( wp.blocks, wp.element, wp.blockEditor, wp.components, wp.serverSideRender, wp.i18n );

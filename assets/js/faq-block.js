/**
 * MomentumLab SEO: FAQ block (questions and answers with FAQ schema).
 *
 * Plain script without a build step. The front end is rendered in PHP.
 *
 * @package MomentumLab\SEO
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var be = wp.blockEditor;
	var c = wp.components;
	var t = window.mlabSeoFaq || {};

	wp.blocks.registerBlockType( 'mlab-seo/faq', {
		apiVersion: 3,
		title: t.title,
		description: t.description,
		category: 'text',
		icon: 'editor-help',
		keywords: [ 'faq', 'schema', 'fragen' ],
		attributes: {
			items: { type: 'array', default: [] },
			open: { type: 'boolean', default: false }
		},
		supports: { html: false, anchor: true, spacing: { margin: true, padding: true } },
		example: { attributes: { items: [ { question: t.question, answer: t.answer } ] } },

		edit: function ( props ) {
			var items = props.attributes.items.length ? props.attributes.items : [ { question: '', answer: '' } ];

			function update( index, key, value ) {
				var next = items.map( function ( item, i ) {
					var changed = Object.assign( {}, item );
					changed[ key ] = value;
					return i === index ? changed : item;
				} );
				props.setAttributes( { items: next } );
			}
			function remove( index ) {
				props.setAttributes( { items: items.filter( function ( item, i ) {
					return i !== index;
				} ) } );
			}
			function up( index ) {
				var next = items.slice();
				next.splice( index - 1, 0, next.splice( index, 1 )[ 0 ] );
				props.setAttributes( { items: next } );
			}

			return el(
				'div',
				be.useBlockProps( { className: 'mlab-seo-faq' } ),
				el( be.InspectorControls, null,
					el( c.PanelBody, { title: t.title },
						el( c.ToggleControl, {
							label: t.open,
							checked: props.attributes.open,
							onChange: function ( value ) {
								props.setAttributes( { open: value } );
							}
						} )
					)
				),
				items.map( function ( item, index ) {
					return el( 'div', { key: index, className: 'mlab-seo-faq__item', style: { borderBottom: '1px solid #ddd', padding: '8px 0' } },
						el( be.RichText, {
							tagName: 'p',
							className: 'mlab-seo-faq__question',
							style: { fontWeight: 600, margin: 0 },
							placeholder: t.question,
							allowedFormats: [],
							value: item.question,
							onChange: function ( value ) {
								update( index, 'question', value );
							}
						} ),
						el( be.RichText, {
							tagName: 'div',
							multiline: false,
							className: 'mlab-seo-faq__answer',
							placeholder: t.answer,
							allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
							value: item.answer,
							onChange: function ( value ) {
								update( index, 'answer', value );
							}
						} ),
						el( 'div', null,
							index > 0 ? el( c.Button, { variant: 'link', onClick: function () {
								up( index );
							} }, t.up ) : null,
							' ',
							el( c.Button, { variant: 'link', isDestructive: true, onClick: function () {
								remove( index );
							} }, t.remove )
						)
					);
				} ),
				el( c.Button, { variant: 'secondary', onClick: function () {
					props.setAttributes( { items: items.concat( [ { question: '', answer: '' } ] ) } );
				} }, t.add )
			);
		},

		save: function () {
			return null;
		}
	} );
}( window.wp ) );

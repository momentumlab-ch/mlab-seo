/**
 * MomentumLab SEO: sidebar in the block editor. One click shows score, Google and social preview and the main fields.
 * It is a view of the SEO box below the content (assets/js/editor.js), which keeps saving all values.
 *
 * @package MomentumLab\SEO
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;
	var c = wp.components;
	var PluginSidebar = ( wp.editor && wp.editor.PluginSidebar ) || wp.editPost.PluginSidebar;
	var MoreItem = ( wp.editor && wp.editor.PluginSidebarMoreMenuItem ) || wp.editPost.PluginSidebarMoreMenuItem;
	var NAME = 'mlab-seo-sidebar';

	function icon() {
		return el( 'svg', { width: 20, height: 20, viewBox: '0 0 20 20', 'aria-hidden': true }, el( 'path', { fill: 'currentColor', d: 'M8.5 2a6.5 6.5 0 0 1 5.2 10.4l4 4-1.3 1.3-4-4A6.5 6.5 0 1 1 8.5 2Zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z' } ) );
	}

	function set( field, value ) {
		document.dispatchEvent( new window.CustomEvent( 'mlab-seo-set', { detail: { field: field, value: value } } ) );
	}

	function state( score ) {
		return window.mlabSeoAnalysis.state( score );
	}

	/* Opens the SEO box below the content for event, schema, robots rules and translations. */
	function openBox() {
		var presenter = document.querySelector( '.edit-post-meta-boxes-main__presenter > button[aria-expanded="false"]' );
		if ( presenter ) {
			presenter.click();
		}
		var box = document.getElementById( 'mlab-seo' );
		if ( box ) {
			box.scrollIntoView( { behavior: 'smooth' } );
		}
	}

	function Serp( props ) {
		var d = props.data;
		return el( 'div', { className: 'mlab-seo-side__serp' },
			el( 'span', { className: 'mlab-seo-side__url' }, d.serpUrl ),
			el( 'span', { className: 'mlab-seo-side__title' }, d.serpTitle ),
			el( 'span', { className: 'mlab-seo-side__desc' }, d.serpDesc )
		);
	}

	function Social( props ) {
		var d = props.data;
		return el( 'div', { className: 'mlab-seo-og' },
			el( 'div', { className: 'mlab-seo-og__img', style: d.image ? { backgroundImage: 'url("' + encodeURI( d.image ) + '")' } : {} } ),
			el( 'div', { className: 'mlab-seo-og__meta' },
				el( 'span', { className: 'mlab-seo-og__title' }, d.ogTitle ),
				el( 'span', { className: 'mlab-seo-og__desc' }, d.ogDesc )
			)
		);
	}

	function Checks( props ) {
		return el( 'ul', { className: 'mlab-seo-checks' }, props.list.map( function ( item, i ) {
			return el( 'li', { key: i, 'data-state': item.state }, item.msg );
		} ) );
	}

	function Fields( props ) {
		var d = props.data;
		return el( wp.element.Fragment, null,
			el( c.TextControl, { label: __( 'SEO title', 'mlab-seo' ), value: d.title, placeholder: d.titlePlaceholder, onChange: function ( v ) {
				set( 'title', v );
			} } ),
			el( c.TextareaControl, { label: __( 'Meta description', 'mlab-seo' ), value: d.desc, placeholder: d.descPlaceholder, rows: 4, onChange: function ( v ) {
				set( 'desc', v );
			} } ),
			el( c.TextControl, { label: __( 'Focus keyword', 'mlab-seo' ), value: d.focus, onChange: function ( v ) {
				set( 'focus', v );
			} } ),
			el( c.ToggleControl, { label: __( 'Show in search engines', 'mlab-seo' ), checked: ! d.noindex, onChange: function ( v ) {
				set( 'noindex', ! v );
			} } )
		);
	}

	function Sidebar() {
		var pair = useState( null );
		var data = pair[ 0 ];
		useEffect( function () {
			function listen( e ) {
				pair[ 1 ]( e.detail );
			}
			document.addEventListener( 'mlab-seo-update', listen );
			document.dispatchEvent( new window.CustomEvent( 'mlab-seo-ready' ) );
			return function () {
				document.removeEventListener( 'mlab-seo-update', listen );
			};
		}, [] );
		if ( ! data ) {
			return el( c.PanelBody, null, el( 'p', null, __( 'Loading …', 'mlab-seo' ) ) );
		}
		var problems = data.list.filter( function ( item ) {
			return 'good' !== item.state;
		} );
		return el( wp.element.Fragment, null,
			el( c.PanelBody, { title: __( 'Google preview', 'mlab-seo' ), className: 'mlab-seo-side' },
				el( 'p', null, el( 'span', { className: 'mlab-seo-tag is-' + state( data.score ) }, data.score ), ' ', __( 'SEO score', 'mlab-seo' ) ),
				el( Serp, { data: data } ),
				el( Fields, { data: data } )
			),
			el( c.PanelBody, { title: __( 'SEO check', 'mlab-seo' ) + ' (' + problems.length + ')', initialOpen: problems.length > 0 },
				el( Checks, { list: data.list } )
			),
			el( c.PanelBody, { title: __( 'Social media preview', 'mlab-seo' ), initialOpen: false },
				el( Social, { data: data } )
			),
			el( c.PanelBody, { title: __( 'More options', 'mlab-seo' ), initialOpen: false },
				el( 'p', null, __( 'Social image and texts, robots rules, canonical, schema, event and translations are in the SEO box below the content.', 'mlab-seo' ) ),
				el( c.Button, { variant: 'secondary', onClick: openBox }, __( 'Open SEO box', 'mlab-seo' ) )
			)
		);
	}

	wp.plugins.registerPlugin( 'mlab-seo', {
		icon: icon,
		render: function () {
			return el( wp.element.Fragment, null,
				el( MoreItem, { target: NAME, icon: icon() }, __( 'SEO', 'mlab-seo' ) ),
				el( PluginSidebar, { name: NAME, title: __( 'SEO', 'mlab-seo' ), icon: icon() }, el( Sidebar ) )
			);
		}
	} );
}( window.wp ) );

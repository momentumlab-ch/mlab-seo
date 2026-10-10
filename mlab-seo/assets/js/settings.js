/**
 * MomentumLab SEO: settings screen (tabs, image pickers, redirect rows).
 *
 * @package MomentumLab\SEO
 */
( function ( $ ) {
	'use strict';

	var cfg = window.mlabSeoSettings || {};
	var $wrap = $( '.mlab-seo-settings' );
	var formTabs = [ 'general', 'publisher', 'ai', 'sitemap' ];

	function show( tab ) {
		if ( ! $wrap.find( '.mlab-seo-nav [data-tab="' + tab + '"]' ).length ) {
			tab = 'general';
		}
		$wrap.find( '.mlab-seo-nav .nav-tab' ).each( function () {
			var on = $( this ).data( 'tab' ) === tab;
			$( this ).toggleClass( 'nav-tab-active', on ).attr( 'aria-current', on ? 'page' : null );
		} );
		$wrap.find( '.mlab-seo-tab' ).each( function () {
			$( this ).prop( 'hidden', $( this ).data( 'tab' ) !== tab );
		} );
		$wrap.find( '.mlab-seo-submit' ).prop( 'hidden', formTabs.indexOf( tab ) < 0 );
		$wrap.find( '.mlab-seo-current-tab' ).val( tab );
	}

	$wrap.on( 'click', '.mlab-seo-nav .nav-tab, [data-tab-link]', function ( e ) {
		var tab = $( this ).data( 'tab' ) || $( this ).data( 'tab-link' );
		e.preventDefault();
		show( tab );
		if ( window.history && history.replaceState ) {
			history.replaceState( null, '', '#' + tab );
		}
	} );
	show( ( window.location.hash || '#general' ).slice( 1 ) );

	/* Image pickers. */
	$wrap.on( 'click', '.mlab-seo-picker__pick', function () {
		var $picker = $( this ).closest( '.mlab-seo-picker' );
		var frame = wp.media( {
			title: cfg.choose,
			button: { text: cfg.use },
			library: { type: 'image' },
			multiple: false
		} );
		frame.on( 'select', function () {
			var a = frame.state().get( 'selection' ).first().toJSON();
			var src = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
			$picker.find( 'input' ).val( a.id );
			$picker.find( '.mlab-seo-picker__preview' ).html( $( '<img alt="">' ).attr( 'src', src ) );
			$picker.find( '.mlab-seo-picker__remove' ).prop( 'hidden', false );
		} );
		frame.open();
	} );
	$wrap.on( 'click', '.mlab-seo-picker__remove', function () {
		var $picker = $( this ).closest( '.mlab-seo-picker' );
		$picker.find( 'input' ).val( '' );
		$picker.find( '.mlab-seo-picker__preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	/* Redirect rows. */
	function addRow( from ) {
		var $body = $wrap.find( '.mlab-seo-redirects tbody' );
		var $last = $body.find( 'tr' ).last();
		var $row = $last;
		if ( $last.find( 'input' ).first().val() ) {
			var index = $body.find( 'tr' ).length;
			$row = $last.clone();
			$row.find( 'input, select' ).each( function () {
				this.name = this.name.replace( /\[\d+\]/, '[' + index + ']' );
			} );
			$row.find( 'input' ).val( '' );
			$row.find( 'select' ).val( '301' );
			$row.find( 'td' ).eq( 3 ).text( '0' );
			$row.appendTo( $body );
		}
		if ( from ) {
			$row.find( 'input' ).first().val( from );
			$row.find( 'input' ).eq( 1 ).trigger( 'focus' );
		} else {
			$row.find( 'input' ).first().trigger( 'focus' );
		}
	}
	$wrap.on( 'click', '.mlab-seo-row-add', function () {
		addRow( '' );
	} );
	$wrap.on( 'click', '.mlab-seo-log-use', function () {
		addRow( $( this ).data( 'path' ) );
	} );
	$wrap.on( 'click', '.mlab-seo-row-remove', function () {
		var $tr = $( this ).closest( 'tr' );
		if ( $tr.siblings().length ) {
			$tr.remove();
		} else {
			$tr.find( 'input' ).val( '' );
		}
	} );
}( jQuery ) );

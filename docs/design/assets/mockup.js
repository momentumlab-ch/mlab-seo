/* MomentumLab SEO – Redesign-Mockups: Menü, Segmente, Sheet, Live-Vorschau. */
( function () {
	'use strict';

	var MENU = [
		[ 'uebersicht', 'Übersicht', '01-uebersicht.html' ],
		[ 'seiten', 'Seiten', '02-seiten.html' ],
		[ 'suche', 'Suchdarstellung', '04-suchdarstellung.html' ],
		[ 'ki', 'KI & Crawler', '05-ki-crawler.html' ],
		[ 'sitemap', 'Sitemap', '06-sitemap.html' ],
		[ 'weiterleitungen', 'Weiterleitungen', '07-weiterleitungen.html' ],
		[ 'einstellungen', 'Einstellungen', '08-einstellungen.html' ]
	];

	function link( href, text, cls ) {
		var a = document.createElement( 'a' );
		a.href = href;
		a.textContent = text;
		if ( cls ) {
			a.className = cls;
		}
		return a;
	}

	/* WordPress-Menü mit aufgeklapptem Eintrag «SEO». */
	function renderMenu( nav ) {
		var here = nav.getAttribute( 'data-here' );
		[ 'Dashboard', 'Beiträge', 'Medien', 'Seiten' ].forEach( function ( label ) {
			nav.appendChild( link( 'Seiten' === label ? '03-editor.html' : '#', label ) );
		} );
		var seo = link( '01-uebersicht.html', 'SEO', 'is-current' );
		var badge = document.createElement( 'span' );
		badge.className = 'click';
		badge.textContent = '1';
		badge.title = 'Einstieg: Klick 1';
		seo.appendChild( badge );
		nav.appendChild( seo );
		var sub = document.createElement( 'div' );
		sub.className = 'sub';
		MENU.forEach( function ( item ) {
			var a = link( item[ 2 ], item[ 1 ], item[ 0 ] === here ? 'is-here' : '' );
			if ( 'weiterleitungen' === item[ 0 ] ) {
				a.appendChild( document.createTextNode( ' ' ) );
				var pro = document.createElement( 'span' );
				pro.className = 'badge pro';
				pro.textContent = 'Pro';
				a.appendChild( pro );
			}
			sub.appendChild( a );
		} );
		nav.appendChild( sub );
		[ 'Darstellung', 'Plugins', 'Einstellungen' ].forEach( function ( label ) {
			nav.appendChild( link( '#', label ) );
		} );
	}

	/* Segmente: Buttons mit data-seg zeigen das Panel mit gleichem data-panel. */
	function initSegments( group ) {
		var scope = document.getElementById( group.getAttribute( 'data-scope' ) );
		group.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button[data-seg]' );
			if ( ! button ) {
				return;
			}
			group.querySelectorAll( 'button' ).forEach( function ( b ) {
				b.setAttribute( 'aria-selected', b === button ? 'true' : 'false' );
			} );
			if ( ! scope ) {
				return;
			}
			scope.querySelectorAll( ':scope > [data-panel]' ).forEach( function ( panel ) {
				panel.hidden = panel.getAttribute( 'data-panel' ) !== button.getAttribute( 'data-seg' );
			} );
		} );
	}

	/* Sheet öffnen und schliessen. */
	function openSheet( title ) {
		var target = document.querySelector( '[data-sheet-title]' );
		if ( target && title ) {
			target.textContent = title;
		}
		document.body.classList.add( 'is-open' );
		var first = document.querySelector( '.sheet input, .sheet textarea' );
		if ( first ) {
			first.focus();
		}
	}

	function closeSheet() {
		document.body.classList.remove( 'is-open' );
	}

	/* Zeichenzähler und Balken: grün im Zielbereich, orange darunter, rot darüber. */
	function updateCounter( input ) {
		var len = input.value.length;
		var min = parseInt( input.getAttribute( 'data-min' ), 10 );
		var max = parseInt( input.getAttribute( 'data-max' ), 10 );
		var field = input.closest( '.field' );
		var count = field && field.querySelector( 'em' );
		var bar = field && field.querySelector( '.meter i' );
		if ( count ) {
			count.textContent = len + ' / ' + max;
		}
		if ( bar ) {
			bar.style.width = Math.min( 100, ( len / max ) * 100 ) + '%';
			bar.style.background = len > max ? 'var(--bad-dot)' : len < min ? 'var(--ok-dot)' : 'var(--good-dot)';
		}
		document.querySelectorAll( '[data-show="' + input.getAttribute( 'data-bind' ) + '"]' ).forEach( function ( el ) {
			el.textContent = input.value || el.getAttribute( 'data-empty' ) || '';
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( 'nav.wp-menu' ).forEach( renderMenu );
		document.querySelectorAll( '.seg[data-scope]' ).forEach( initSegments );
		document.querySelectorAll( '[data-bind]' ).forEach( function ( input ) {
			updateCounter( input );
			input.addEventListener( 'input', function () {
				updateCounter( input );
			} );
		} );
		document.addEventListener( 'click', function ( event ) {
			var opener = event.target.closest( '[data-open-sheet]' );
			if ( opener ) {
				event.preventDefault();
				openSheet( opener.getAttribute( 'data-open-sheet' ) );
			}
			if ( event.target.closest( '[data-close-sheet]' ) ) {
				closeSheet();
			}
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				closeSheet();
			}
		} );
		if ( '#bearbeiten' === window.location.hash ) {
			openSheet();
		}
	} );
}() );

/**
 * MomentumLab SEO: page list. Opens the edit panel on a row, previews while typing, saves over REST,
 * and checks pages without a score that appear in the list.
 *
 * @package MomentumLab\SEO
 */
( function () {
	'use strict';

	var __ = wp.i18n.__;
	var A = window.mlabSeoAnalysis;
	var cfg = window.mlabSeoPages || {};
	var sheet = document.querySelector( '.mlab-seo-sheet' );
	var scrim = document.querySelector( '.mlab-seo-scrim' );
	if ( ! sheet ) {
		return;
	}
	var form = sheet.querySelector( 'form' );
	var current = null;
	var opener = null;

	function fetchPosts( ids ) {
		return wp.apiFetch( { path: '/mlab-seo/v1/posts?' + ids.map( function ( id ) {
			return 'ids[]=' + encodeURIComponent( id );
		} ).join( '&' ) } );
	}

	function show( name, value ) {
		sheet.querySelectorAll( '[data-show="' + name + '"]' ).forEach( function ( el ) {
			el.textContent = value;
		} );
	}

	function values() {
		return {
			title: form.title.value,
			description: form.description.value,
			focus: form.focus.value,
			noindex: form.index.checked ? 0 : 1,
			llms: form.llms.value,
			canonical: form.canonical.value,
			page_type: form.page_type.value,
			breadcrumb: form.breadcrumb.value,
			og_title: form.og_title.value,
			og_desc: form.og_desc.value
		};
	}

	function level( isBad, isOk ) {
		if ( isBad ) {
			return 'bad';
		}
		return isOk ? 'ok' : 'good';
	}

	function meter( name, ratio, state, count ) {
		var m = sheet.querySelector( '[data-for="' + name + '"]' );
		m.setAttribute( 'data-state', state );
		m.querySelector( 'i' ).style.width = Math.min( 100, Math.round( ratio * 100 ) ) + '%';
		sheet.querySelector( '[data-count="' + name + '"]' ).textContent = count;
	}

	function input( post, v ) {
		return {
			title: A.resolve( v.title, post.name, post.vars ) || post.fallback.title,
			desc: A.resolve( v.description, post.name, post.vars ),
			focus: v.focus,
			content: post.content,
			slug: post.slug,
			url: post.url,
			image: !! post.fields.image,
			featured: post.featured,
			noindex: !! v.noindex,
			isFront: post.isFront,
			homeUrl: cfg.homeUrl
		};
	}

	/* Previews, meters and check, on every change. */
	function update() {
		var post = current;
		var v = values();
		var data = input( post, v );
		var desc = data.desc || post.fallback.description;
		var width = A.textWidth( data.title );
		var result = A.analyze( data );

		show( 'serpTitle', width > 580 ? data.title.slice( 0, Math.floor( data.title.length * 570 / width ) ) + ' …' : data.title );
		show( 'serpDesc', desc.length > 160 ? desc.slice( 0, 157 ) + ' …' : desc );
		show( 'url', post.crumbs.length && ! v.noindex ? [ cfg.homeUrl.replace( /\/$/, '' ) ].concat( post.crumbs, [ post.name ] ).join( ' › ' ) : post.url );
		show( 'ogTitle', A.resolve( v.og_title, post.name, post.vars ) || data.title );
		show( 'ogDesc', A.resolve( v.og_desc, post.name, post.vars ) || desc );
		/* translators: %d: number of characters. */
		meter( 'title', width / 580, level( width > 580, data.title.length < 30 ), A.fmt( __( '%d characters', 'mlab-seo' ), data.title.length ) );
		var dl = data.desc.length;
		/* translators: %d: number of characters. */
		meter( 'description', dl / 160, level( ! dl || dl > 160, dl < 120 ), A.fmt( __( '%d characters', 'mlab-seo' ), dl ) );

		var list = sheet.querySelector( '[data-checks]' );
		list.textContent = '';
		result.list.forEach( function ( c ) {
			var li = document.createElement( 'li' );
			li.setAttribute( 'data-state', c.state );
			li.textContent = c.msg;
			list.appendChild( li );
		} );
		var badge = sheet.querySelector( '[data-show="scoreBadge"]' );
		badge.className = 'mlab-seo-tag is-' + A.state( result.score );
		badge.textContent = result.score;
		return result;
	}

	function fill( post ) {
		var f = post.fields;
		current = post;
		[ 'title', 'description', 'focus', 'llms', 'canonical', 'page_type', 'breadcrumb', 'og_title', 'og_desc' ].forEach( function ( key ) {
			form[ key ].value = f[ key ] || '';
		} );
		form.index.checked = ! f.noindex;
		form.title.placeholder = post.fallback.title;
		form.description.placeholder = post.fallback.description;
		show( 'name', post.name );
		show( 'site', cfg.siteName || '' );
		show( 'host', cfg.homeUrl.replace( /^https?:\/\//, '' ).replace( /\/.*$/, '' ) );
		show( 'date', post.date ? post.date + ' — ' : '' );
		sheet.querySelector( '[data-href="edit"]' ).href = post.edit;
		sheet.querySelector( '[data-image]' ).style.backgroundImage = post.image ? 'url("' + encodeURI( post.image ) + '")' : '';
		update();
	}

	function open( id ) {
		opener = document.activeElement;
		scrim.hidden = false;
		sheet.hidden = false;
		show( 'name', __( 'Loading …', 'mlab-seo' ) );
		fetchPosts( [ id ] ).then( function ( posts ) {
			if ( posts[ 0 ] ) {
				fill( posts[ 0 ] );
				form.title.focus();
			}
		} );
	}

	function close() {
		scrim.hidden = true;
		sheet.hidden = true;
		current = null;
		if ( opener ) {
			opener.focus();
		}
	}

	function rowState( id, post ) {
		var row = document.querySelector( 'tr[data-id="' + id + '"]' );
		if ( ! row ) {
			return;
		}
		var badge = row.querySelector( '.column-score .mlab-seo-tag' );
		if ( badge && ! post.fields.noindex ) {
			badge.className = 'mlab-seo-tag is-' + A.state( post.fields.score );
			badge.textContent = post.fields.score;
		}
		var desc = row.querySelector( '.mlab-seo-row__desc' );
		if ( desc && post.fields.description ) {
			desc.className = 'mlab-seo-row__desc';
			desc.textContent = post.fields.description;
		}
	}

	function saveFields( id, fields ) {
		return wp.apiFetch( { path: '/mlab-seo/v1/posts/' + id, method: 'POST', data: { fields: fields } } ).then( function ( post ) {
			rowState( id, post );
			return post;
		} );
	}

	function save() {
		var v = values();
		v.score = update().score;
		var button = sheet.querySelector( '[data-save]' );
		button.disabled = true;
		saveFields( current.id, v ).then( close ).catch( function ( err ) {
			window.alert( ( err && err.message ) || __( 'Not saved. Please try again.', 'mlab-seo' ) );
		} ).then( function () {
			button.disabled = false;
		} );
	}

	/* Description suggestion from the start of the page text, cut at a word. */
	function suggest() {
		var text = A.plainText( current.content );
		if ( text.length > 155 ) {
			text = text.slice( 0, 155 ).replace( /\s+\S*$/, '' ) + ' …';
		}
		form.description.value = text;
		update();
	}

	/* Pages without a score are checked once when they show up in the list. */
	function scoreMissing() {
		var ids = Array.prototype.map.call( document.querySelectorAll( '.column-score [data-score=""]' ), function ( el ) {
			return el.closest( 'tr' ).getAttribute( 'data-id' );
		} );
		if ( ! ids.length ) {
			return;
		}
		fetchPosts( ids ).then( function ( posts ) {
			return posts.reduce( function ( chain, post ) {
				return chain.then( function () {
					var score = A.analyze( input( post, { title: post.fields.title, description: post.fields.description, focus: post.fields.focus, noindex: post.fields.noindex } ) ).score;
					return saveFields( post.id, { score: score } );
				} );
			}, Promise.resolve() );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		var row = e.target.closest( '.mlab-seo-list tbody tr[data-id]' );
		if ( row && ! e.target.closest( 'input, a, .check-column' ) ) {
			open( row.getAttribute( 'data-id' ) );
		}
		if ( e.target.closest( '.mlab-seo-sheet [data-close], .mlab-seo-scrim' ) ) {
			close();
		}
	} );
	document.addEventListener( 'keydown', function ( e ) {
		var row = e.target.closest && e.target.closest( '.mlab-seo-list tbody tr[data-id]' );
		if ( 'Enter' === e.key && row && e.target === row ) {
			open( row.getAttribute( 'data-id' ) );
		}
		if ( 'Escape' === e.key && ! sheet.hidden ) {
			close();
		}
	} );
	form.addEventListener( 'input', update );
	form.addEventListener( 'change', update );
	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
	} );
	sheet.querySelector( '[data-save]' ).addEventListener( 'click', save );
	sheet.querySelector( '[data-suggest]' ).addEventListener( 'click', suggest );

	var edit = new URLSearchParams( window.location.search ).get( 'edit' );
	if ( edit ) {
		open( parseInt( edit, 10 ) );
	}
	scoreMissing();
}() );

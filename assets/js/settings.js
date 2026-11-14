/**
 * MomentumLab SEO: SEO screens (segments, autosave, previews, image pickers, redirect rows).
 *
 * @package MomentumLab\SEO
 */
( function ( $ ) {
	'use strict';

	var __ = wp.i18n.__;
	var cfg = window.mlabSeoSettings || {};
	var $wrap = $( '.mlab-seo-app' );

	/* Links from before 1.8.0 (one screen with tabs) lead to the new pages. */
	var legacy = ( cfg.legacy || {} )[ window.location.hash.slice( 1 ) ];
	if ( legacy ) {
		window.location.replace( legacy );
		return;
	}
	$( document.body ).addClass( 'mlab-seo-js' );

	/* Segments ------------------------------------------------------------ */
	function show( key ) {
		var $sections = $wrap.find( '.mlab-seo-segment' );
		if ( ! $sections.filter( '[data-segment="' + key + '"]' ).length ) {
			key = $sections.first().data( 'segment' );
		}
		$sections.each( function () {
			$( this ).prop( 'hidden', $( this ).data( 'segment' ) !== key );
		} );
		$wrap.find( '.mlab-seo-seg a' ).each( function () {
			$( this ).attr( 'aria-current', $( this ).data( 'segment' ) === key ? 'page' : null );
		} );
		loadPreviews( $sections.filter( '[data-segment="' + key + '"]' ) );
	}
	$wrap.on( 'click', '.mlab-seo-seg a, [data-go]', function ( e ) {
		var key = $( this ).data( 'segment' ) || $( this ).data( 'go' );
		e.preventDefault();
		show( key );
		history.replaceState( null, '', '#' + key );
		window.scrollTo( 0, 0 );
	} );
	$( window ).on( 'hashchange', function () {
		show( window.location.hash.slice( 1 ) );
	} );

	/* Live previews of robots.txt and llms.txt, read from the website itself. */
	function loadPreviews( $scope ) {
		$scope.find( '[data-preview]' ).each( function () {
			var el = this;
			window.fetch( el.getAttribute( 'data-preview' ), { cache: 'no-store', credentials: 'omit' } )
				.then( function ( r ) {
					return r.ok ? r.text() : Promise.reject( r.status );
				} )
				.then( function ( text ) {
					el.textContent = text.length > 20000 ? text.slice( 0, 20000 ) + '\n…' : text;
				} )
				.catch( function () {
					el.textContent = __( 'The file could not be loaded.', 'mlab-seo' );
				} );
		} );
	}

	/* Autosave: each segment form saves its own settings. ---------------- */
	function formValues( form ) {
		var values = {};
		new window.FormData( form ).forEach( function ( value, name ) {
			if ( name.indexOf( 'mlab_seo[' ) !== 0 ) {
				return;
			}
			var keys = name.slice( 8 ).match( /\[[^\]]*\]/g ).map( function ( k ) {
				return k.slice( 1, -1 );
			} );
			var node = values;
			for ( var i = 0; i < keys.length; i++ ) {
				if ( '' === keys[ i + 1 ] ) {
					node[ keys[ i ] ] = ( node[ keys[ i ] ] || [] ).concat( [ value ] );
					return;
				}
				if ( i === keys.length - 1 ) {
					node[ keys[ i ] ] = value;
					return;
				}
				node = node[ keys[ i ] ] = node[ keys[ i ] ] || {};
			}
		} );
		return values;
	}

	var $status = $wrap.find( '.mlab-seo-saved' );
	var $last = $wrap.find( '.mlab-seo-savedlast' );
	/* After the toast fades, a quiet line in the head keeps telling when the last change was saved. */
	function savedAt() {
		var now = new Date();
		var time = ( '0' + now.getHours() ).slice( -2 ) + ':' + ( '0' + now.getMinutes() ).slice( -2 );
		/* translators: %s: time of day, for example 14:32. */
		$last.text( '✓ ' + __( 'Saved at %s', 'mlab-seo' ).replace( '%s', time ) );
	}
	var timers = {};
	function save( form ) {
		clearTimeout( timers.status );
		$status.removeClass( 'is-error' ).addClass( 'is-saving' ).text( __( 'Saving …', 'mlab-seo' ) );
		wp.apiFetch( {
			path: '/mlab-seo/v1/settings',
			method: 'POST',
			data: { page: form.getAttribute( 'data-page' ), segment: form.getAttribute( 'data-segment' ), values: formValues( form ) }
		} ).then( function () {
			$status.removeClass( 'is-saving' ).text( __( 'Saved', 'mlab-seo' ) );
			savedAt();
			timers.status = setTimeout( function () {
				$status.text( '' );
			}, 4000 );
			loadPreviews( $( form ).closest( '.mlab-seo-segment' ) );
		} ).catch( function ( err ) {
			$status.removeClass( 'is-saving' ).addClass( 'is-error' ).text( __( 'Not saved.', 'mlab-seo' ) + ' ' + ( ( err && err.message ) || __( 'Please try again.', 'mlab-seo' ) ) );
		} );
	}
	function schedule( form, delay ) {
		var key = form.getAttribute( 'data-segment' );
		clearTimeout( timers[ key ] );
		timers[ key ] = setTimeout( function () {
			save( form );
		}, delay );
	}
	$wrap.on( 'change', '.mlab-seo-autosave', function ( e ) {
		schedule( this, 'checkbox' === e.target.type || 'SELECT' === e.target.tagName ? 150 : 400 );
	} );
	$wrap.on( 'input', '.mlab-seo-autosave input[type="text"], .mlab-seo-autosave textarea', function () {
		schedule( this.form, 1200 );
	} );
	$wrap.on( 'submit', '.mlab-seo-autosave', function ( e ) {
		e.preventDefault();
		save( this );
	} );

	/* AI writing: save or remove a key. The key is never read back, only its end is shown. */
	function keyRequest( provider, key ) {
		clearTimeout( timers.status );
		$status.removeClass( 'is-error' ).addClass( 'is-saving' ).text( __( 'Saving …', 'mlab-seo' ) );
		wp.apiFetch( { path: '/mlab-seo/v1/ai/key', method: 'POST', data: { provider: provider, key: key } } ).then( function ( r ) {
			$status.removeClass( 'is-saving' ).text( r.saved ? __( 'Key saved', 'mlab-seo' ) : __( 'Key removed', 'mlab-seo' ) );
			window.setTimeout( function () {
				window.location.reload();
			}, 900 );
		} ).catch( function ( err ) {
			$status.removeClass( 'is-saving' ).addClass( 'is-error' ).text( __( 'Not saved.', 'mlab-seo' ) + ' ' + ( ( err && err.message ) || __( 'Please try again.', 'mlab-seo' ) ) );
		} );
	}
	$wrap.on( 'click', '[data-ai-key-save]', function () {
		var provider = $( this ).data( 'ai-key-save' );
		keyRequest( provider, $.trim( $wrap.find( '[data-ai-key-input="' + provider + '"]' ).val() ) );
	} );
	$wrap.on( 'keydown', '[data-ai-key-input]', function ( e ) {
		if ( 13 === e.which ) {
			e.preventDefault();
			$wrap.find( '[data-ai-key-save="' + $( this ).data( 'ai-key-input' ) + '"]' ).trigger( 'click' );
		}
	} );
	$wrap.on( 'click', '[data-ai-key-remove]', function () {
		keyRequest( $( this ).data( 'ai-key-remove' ), '' );
	} );

	/* AI crawler quick choice. */
	$wrap.on( 'click', '[data-bots]', function () {
		var mode = $( this ).data( 'bots' );
		var $form = $( this ).closest( 'form' );
		$form.find( 'input[data-search]' ).each( function () {
			this.checked = 'all' === mode || ( 'search' === mode && '1' === this.getAttribute( 'data-search' ) );
		} );
		$( this ).addClass( 'is-on' ).siblings().removeClass( 'is-on' );
		save( $form[ 0 ] );
	} );

	/* Copy an address. */
	$wrap.on( 'click', '[data-copy]', function () {
		var $btn = $( this );
		var text = $btn.closest( '.mlab-seo-address' ).find( '[data-copy-source]' ).text();
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( text ).then( function () {
				$btn.text( __( 'Copied', 'mlab-seo' ) );
			} );
		}
	} );

	/* Lists filter by content type on change. */
	$wrap.on( 'change', 'select[data-autosubmit]', function () {
		this.form.submit();
	} );

	if ( $wrap.length ) {
		show( window.location.hash.slice( 1 ) );
	}

	/* Image pickers, also on the edit screen of categories and tags. */
	$( document ).on( 'click', '.mlab-seo-picker__pick', function () {
		var $picker = $( this ).closest( '.mlab-seo-picker' );
		var frame = wp.media( {
			title: __( 'Choose image', 'mlab-seo' ),
			button: { text: __( 'Use this image', 'mlab-seo' ) },
			library: { type: 'image' },
			multiple: false
		} );
		frame.on( 'select', function () {
			var a = frame.state().get( 'selection' ).first().toJSON();
			var src = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
			$picker.find( 'input' ).val( a.id ).trigger( 'change' );
			$picker.find( '.mlab-seo-picker__preview' ).html( $( '<img alt="">' ).attr( 'src', src ) );
			$picker.find( '.mlab-seo-picker__remove' ).prop( 'hidden', false );
		} );
		frame.open();
	} );
	$( document ).on( 'click', '.mlab-seo-picker__remove', function () {
		var $picker = $( this ).closest( '.mlab-seo-picker' );
		$picker.find( 'input' ).val( '' ).trigger( 'change' );
		$picker.find( '.mlab-seo-picker__preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	/* Redirect rows. */
	function addRow( from ) {
		var $body = $( '.mlab-seo-redirects tbody' );
		var $last = $body.find( 'tr' ).last();
		var $row = $last;
		if ( $last.find( 'input' ).first().val() ) {
			var index = $body.find( 'tr' ).length;
			$row = $last.clone();
			$row.find( 'input, select' ).each( function () {
				this.name = this.name.replace( /\[\d+\]/, '[' + index + ']' );
			} );
			$row.find( 'input[type="text"]' ).val( '' );
			$row.find( 'input[type="checkbox"]' ).prop( 'checked', false );
			$row.find( 'select' ).val( '301' );
			$row.find( 'td' ).eq( 4 ).text( '0' );
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
			$tr.find( 'input[type="text"]' ).val( '' );
			$tr.find( 'input[type="checkbox"]' ).prop( 'checked', false );
		}
	} );
}( jQuery ) );

/**
 * MomentumLab SEO: editor box (tabs, Google preview, SEO check, social image, sub-events).
 *
 * @package MomentumLab\SEO
 */
( function ( $ ) {
	'use strict';

	var cfg = window.mlabSeoEditor || {};
	var t = cfg.i18n || {};
	var $box = $( '.mlab-seo' );
	if ( ! $box.length ) {
		return;
	}

	var $title = $( '#mlab-seo-title' );
	var $desc = $( '#mlab-seo-description' );
	var $focus = $( '#mlab-seo-focus' );
	var isFront = '1' === String( $box.data( 'front' ) );
	var frontPlaceholder = $title.attr( 'placeholder' );
	var canvas = document.createElement( 'canvas' ).getContext( '2d' );

	function fmt( str, value ) {
		return String( str ).replace( /%[ds]/, value ).replace( '%%', '%' );
	}

	function escapeRegExp( s ) {
		return s.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	/* Tabs ---------------------------------------------------------------- */
	$box.on( 'click', '.mlab-seo__tab', function () {
		var tab = $( this ).data( 'tab' );
		$box.find( '.mlab-seo__tab' ).removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
		$( this ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );
		$box.find( '.mlab-seo__panel' ).each( function () {
			var on = $( this ).data( 'panel' ) === tab;
			$( this ).toggleClass( 'is-active', on ).prop( 'hidden', ! on );
		} );
	} );

	/* Editor data (block editor or classic editor) ------------------------ */
	function editor() {
		var data = { title: '', content: '', slug: '', url: '', featured: 0 };
		var select = window.wp && wp.data && wp.data.select ? wp.data.select( 'core/editor' ) : null;
		if ( select && select.getEditedPostContent ) {
			data.title = select.getEditedPostAttribute( 'title' ) || '';
			data.content = select.getEditedPostContent() || '';
			data.slug = select.getEditedPostSlug ? select.getEditedPostSlug() || '' : '';
			data.url = select.getPermalink ? select.getPermalink() || '' : '';
			data.featured = select.getEditedPostAttribute( 'featured_media' ) || 0;
			return data;
		}
		data.title = $( '#title' ).val() || '';
		if ( window.tinymce && tinymce.get( 'content' ) && ! tinymce.get( 'content' ).isHidden() ) {
			data.content = tinymce.get( 'content' ).getContent();
		} else {
			data.content = $( '#content' ).val() || '';
		}
		data.slug = $( '#editable-post-name-full' ).text() || $( '#post_name' ).val() || '';
		data.url = $( '#sample-permalink a' ).attr( 'href' ) || '';
		data.featured = parseInt( $( '#_thumbnail_id' ).val(), 10 ) > 0 ? 1 : 0;
		return data;
	}

	function textWidth( text ) {
		canvas.font = '20px Arial';
		return Math.round( canvas.measureText( text ).width );
	}

	/* Preview and checks -------------------------------------------------- */
	function update() {
		var ed = editor();
		if ( ! isFront ) {
			$title.attr( 'placeholder', ( ed.title || '' ) + ' ' + cfg.separator + ' ' + cfg.siteName );
		} else {
			$title.attr( 'placeholder', frontPlaceholder );
		}

		var title = $.trim( $title.val() ) || $title.attr( 'placeholder' ) || '';
		var desc = $.trim( $desc.val() ) || $desc.attr( 'placeholder' ) || '';
		var ownDesc = $.trim( $desc.val() );
		var focus = $.trim( $focus.val() ).toLowerCase();
		var width = textWidth( title );

		$box.find( '.mlab-seo__serp-title' ).text( width > 580 ? title.slice( 0, Math.floor( title.length * 570 / width ) ) + ' …' : title );
		$box.find( '.mlab-seo__serp-desc' ).text( desc.length > 160 ? desc.slice( 0, 157 ) + ' …' : desc );
		if ( ed.url ) {
			$box.find( '.mlab-seo__serp-url' ).text( isFront ? cfg.homeUrl : ed.url );
		}

		meter( 'title', width / 580, width > 580 ? 'bad' : ( title.length < 30 ? 'ok' : 'good' ) );
		$box.find( '[data-hint="title"]' ).text( fmt( t.chars, title.length ) + ' · ' + fmt( t.pixels, width ) );
		var dl = ownDesc.length;
		meter( 'description', dl / 160, ! dl ? 'bad' : ( dl > 160 ? 'bad' : ( dl < 120 ? 'ok' : 'good' ) ) );
		$box.find( '[data-hint="description"]' ).text( fmt( t.chars, dl ) );

		checks( ed, title, ownDesc, focus );
	}

	function meter( name, ratio, state ) {
		$box.find( '.mlab-seo__meter[data-for="' + name + '"]' )
			.attr( 'data-state', state )
			.find( 'span' ).css( 'width', Math.min( 100, Math.round( ratio * 100 ) ) + '%' );
	}

	function checks( ed, title, desc, focus ) {
		var list = [];
		var doc = $( '<div>' ).html( ed.content.replace( /<!--[\s\S]*?-->/g, '' ) );
		doc.find( 'script,style' ).remove();
		var text = $.trim( doc.text().replace( /\s+/g, ' ' ) );
		var words = text ? text.split( ' ' ).length : 0;
		var width = textWidth( title );
		var titleLower = title.toLowerCase();

		function add( state, msg ) {
			list.push( { state: state, msg: msg } );
		}

		if ( $( '#mlab-seo-noindex' ).is( ':checked' ) ) {
			add( 'ok', t.noindex );
		}

		add( width > 580 ? 'bad' : ( title.length < 30 ? 'ok' : 'good' ), width > 580 ? t.titleLong : ( title.length < 30 ? t.titleShort : t.titleLength ) );
		if ( ! desc ) {
			add( 'bad', t.descEmpty );
		} else if ( desc.length > 160 ) {
			add( 'ok', t.descLong );
		} else if ( desc.length < 120 ) {
			add( 'ok', t.descShort );
		} else {
			add( 'good', t.descGood );
		}

		if ( focus ) {
			var re = new RegExp( '(^|[^\\p{L}\\p{N}])' + escapeRegExp( focus ) + '(?=$|[^\\p{L}\\p{N}])', 'giu' );
			var slugFocus = focus.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, '-' );
			var intro = $.trim( doc.find( 'p' ).first().text() ).toLowerCase();
			var headingText = doc.find( 'h2,h3' ).text().toLowerCase();
			var hits = ( text.toLowerCase().match( re ) || [] ).length;
			var density = words ? ( hits * focus.split( ' ' ).length * 100 / words ) : 0;
			var d = density.toFixed( 1 );

			add( titleLower.indexOf( focus ) > -1 ? 'good' : 'bad', titleLower.indexOf( focus ) > -1 ? t.focusTitle : t.focusTitleNo );
			add( desc.toLowerCase().indexOf( focus ) > -1 ? 'good' : 'ok', desc.toLowerCase().indexOf( focus ) > -1 ? t.focusDesc : t.focusDescNo );
			add( intro.indexOf( focus ) > -1 ? 'good' : 'ok', intro.indexOf( focus ) > -1 ? t.focusIntro : t.focusIntroNo );
			if ( ! isFront ) {
				var inUrl = decodeURIComponent( ed.slug || ed.url || '' ).toLowerCase().indexOf( slugFocus ) > -1;
				add( inUrl ? 'good' : 'ok', inUrl ? t.focusUrl : t.focusUrlNo );
			}
			add( headingText.indexOf( focus ) > -1 ? 'good' : 'ok', headingText.indexOf( focus ) > -1 ? t.focusHeading : t.focusHeadingNo );
			if ( words >= 100 ) {
				add( density < 0.5 ? 'ok' : ( density > 3 ? 'bad' : 'good' ), fmt( density < 0.5 ? t.densityLow : ( density > 3 ? t.densityHigh : t.density ), d ) );
			}
		} else {
			add( 'ok', t.noFocus );
		}

		add( words >= 300 ? 'good' : 'ok', fmt( words >= 300 ? t.words : t.wordsLow, words ) );
		if ( doc.find( 'h1' ).length ) {
			add( 'bad', t.h1 );
		}
		add( doc.find( 'h2,h3' ).length ? 'good' : 'ok', doc.find( 'h2,h3' ).length ? t.headings : t.headingsNo );

		var missingAlt = doc.find( 'img' ).filter( function () {
			return ! $.trim( $( this ).attr( 'alt' ) || '' );
		} ).length;
		if ( doc.find( 'img' ).length ) {
			add( missingAlt ? 'ok' : 'good', missingAlt ? fmt( t.altNo, missingAlt ) : t.alt );
		}

		var home = cfg.homeUrl.replace( /^https?:/, '' );
		var internal = 0;
		var external = 0;
		doc.find( 'a[href]' ).each( function () {
			var href = $( this ).attr( 'href' );
			if ( /^(mailto|tel):|^#/.test( href ) ) {
				return;
			}
			if ( href.charAt( 0 ) === '/' && href.charAt( 1 ) !== '/' || href.replace( /^https?:/, '' ).indexOf( home ) === 0 ) {
				internal++;
			} else if ( /^https?:\/\//.test( href ) ) {
				external++;
			}
		} );
		add( internal ? 'good' : 'ok', internal ? t.internal : t.internalNo );
		add( external ? 'good' : 'ok', external ? t.external : t.externalNo );

		var hasImage = parseInt( $( '#mlab-seo-image' ).val(), 10 ) > 0 || ed.featured;
		add( hasImage ? 'good' : 'ok', hasImage ? t.image : t.imageNo );

		/* Score: good = 1, can be improved = 0.5, problem = 0. */
		var scored = list.filter( function ( c ) {
			return c.msg !== t.noindex && c.msg !== t.noFocus;
		} );
		var points = scored.reduce( function ( sum, c ) {
			return sum + ( c.state === 'good' ? 1 : ( c.state === 'ok' ? 0.5 : 0 ) );
		}, 0 );
		var score = scored.length ? Math.max( 1, Math.round( points * 100 / scored.length ) ) : 0;
		$( '#mlab-seo-score' ).val( score );
		$box.find( '.mlab-seo__score' ).text( score ).attr( 'data-state', score >= 80 ? 'good' : ( score >= 50 ? 'ok' : 'bad' ) );

		var order = { bad: 0, ok: 1, good: 2 };
		list.sort( function ( a, b ) {
			return order[ a.state ] - order[ b.state ];
		} );
		var label = { bad: t.problem, ok: t.improve, good: t.good };
		var $ul = $box.find( '.mlab-seo__checks' ).empty();
		list.forEach( function ( c ) {
			$( '<li>' ).attr( 'data-state', c.state )
				.append( $( '<span class="screen-reader-text">' ).text( label[ c.state ] + ': ' ) )
				.append( document.createTextNode( c.msg ) )
				.appendTo( $ul );
		} );
	}

	var timer;
	function schedule() {
		clearTimeout( timer );
		timer = setTimeout( update, 300 );
	}

	$box.on( 'input change', 'input, textarea, select', schedule );
	if ( window.wp && wp.data && wp.data.subscribe ) {
		wp.data.subscribe( schedule );
	}
	$( '#title, #content' ).on( 'input', schedule );
	$( document ).on( 'tinymce-editor-init', function ( e, ed ) {
		ed.on( 'keyup change', schedule );
	} );
	update();

	/* Social image ------------------------------------------------------- */
	var frame;
	$box.on( 'click', '.mlab-seo__image-pick', function ( e ) {
		e.preventDefault();
		if ( ! frame ) {
			frame = wp.media( {
				title: t.chooseImage,
				button: { text: t.useImage },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				var src = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
				$( '#mlab-seo-image' ).val( a.id ).trigger( 'change' );
				$box.find( '.mlab-seo__image-preview' ).html( $( '<img alt="">' ).attr( 'src', src ) );
				$box.find( '.mlab-seo__image-remove' ).prop( 'hidden', false );
			} );
		}
		frame.open();
	} );
	$box.on( 'click', '.mlab-seo__image-remove', function () {
		$( '#mlab-seo-image' ).val( '' ).trigger( 'change' );
		$box.find( '.mlab-seo__image-preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	/* Sub-events ---------------------------------------------------------- */
	$box.on( 'click', '.mlab-seo__sub-add', function () {
		var $rows = $box.find( '.mlab-seo__subs tbody tr' );
		var $row = $rows.last().clone();
		var index = $rows.length;
		$row.find( 'input' ).each( function () {
			this.name = this.name.replace( /\[sub\]\[\d+\]/, '[sub][' + index + ']' );
			this.value = '';
		} );
		$row.appendTo( $box.find( '.mlab-seo__subs tbody' ) ).find( 'input' ).first().trigger( 'focus' );
	} );
	$box.on( 'click', '.mlab-seo__sub-remove', function () {
		var $tr = $( this ).closest( 'tr' );
		if ( $tr.siblings().length ) {
			$tr.remove();
		} else {
			$tr.find( 'input' ).val( '' );
		}
	} );
}( jQuery ) );

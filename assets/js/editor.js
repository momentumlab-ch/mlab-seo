/**
 * MomentumLab SEO: editor box (tabs, Google preview, SEO check, social image, sub-events).
 *
 * @package MomentumLab\SEO
 */
( function ( $ ) {
	'use strict';

	var cfg = window.mlabSeoEditor || {};
	var __ = wp.i18n.__;
	var $box = $( '.mlab-seo' );
	if ( ! $box.length ) {
		return;
	}

	var $title = $( '#mlab-seo-seo-title' );
	var $desc = $( '#mlab-seo-description' );
	var $focus = $( '#mlab-seo-focus' );
	var isFront = '1' === String( $box.data( 'front' ) );
	var frontPlaceholder = $title.attr( 'placeholder' );
	var canvas = document.createElement( 'canvas' ).getContext( '2d' );

	function fmt( str, value ) {
		return String( str ).replace( /%[ds]/, value ).replace( '%%', '%' );
	}

	/* Variables like %title% as on the website; %title% follows the editor. */
	function resolve( text, title ) {
		var vars = cfg.vars || {};
		return String( text || '' ).replace( /%%?([a-z_]+)%%?/gi, function ( match, name ) {
			name = name.toLowerCase();
			if ( 'title' === name ) {
				return title || '';
			}
			return Object.prototype.hasOwnProperty.call( vars, name ) ? vars[ name ] : match;
		} ).replace( /\s+/g, ' ' ).trim();
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
			data.content += faqText();
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

	/* FAQ blocks are rendered on the server; add their text for the checks. */
	function faqText() {
		var blockEditor = wp.data.select( 'core/block-editor' );
		var html = '';
		function walk( blocks ) {
			( blocks || [] ).forEach( function ( block ) {
				if ( 'mlab-seo/faq' === block.name ) {
					( block.attributes.items || [] ).forEach( function ( item ) {
						html += ' <h3>' + ( item.question || '' ) + '</h3> <p>' + ( item.answer || '' ) + '</p> ';
					} );
				}
				walk( block.innerBlocks );
			} );
		}
		if ( blockEditor && blockEditor.getBlocks ) {
			walk( blockEditor.getBlocks() );
		}
		return html;
	}

	function textWidth( text ) {
		canvas.font = '20px Arial';
		return Math.round( canvas.measureText( text ).width );
	}

	/* Preview and checks -------------------------------------------------- */
	function update() {
		var ed = editor();
		if ( ! isFront ) {
			$title.attr( 'placeholder', resolve( cfg.titlePattern, ed.title ) );
		} else {
			$title.attr( 'placeholder', frontPlaceholder );
		}

		var title = resolve( $title.val(), ed.title ) || $title.attr( 'placeholder' ) || '';
		var desc = resolve( $desc.val(), ed.title ) || $desc.attr( 'placeholder' ) || '';
		var ownDesc = resolve( $desc.val(), ed.title );
		var focus = $.trim( $focus.val() ).toLowerCase();
		var width = textWidth( title );

		$box.find( '.mlab-seo__serp-title' ).text( width > 580 ? title.slice( 0, Math.floor( title.length * 570 / width ) ) + ' …' : title );
		$box.find( '.mlab-seo__serp-desc' ).text( desc.length > 160 ? desc.slice( 0, 157 ) + ' …' : desc );
		if ( ed.url ) {
			$box.find( '.mlab-seo__serp-url' ).text( isFront ? cfg.homeUrl : ed.url );
		}

		meter( 'title', width / 580, width > 580 ? 'bad' : ( title.length < 30 ? 'ok' : 'good' ) );
		$box.find( '[data-hint="title"]' ).text( fmt( /* translators: %d: number of characters. */ __( '%d characters', 'mlab-seo' ), title.length ) + ' · ' + fmt( /* translators: %d: width in pixels. */ __( 'about %d px of 580 px', 'mlab-seo' ), width ) );
		var dl = ownDesc.length;
		meter( 'description', dl / 160, ! dl ? 'bad' : ( dl > 160 ? 'bad' : ( dl < 120 ? 'ok' : 'good' ) ) );
		$box.find( '[data-hint="description"]' ).text( fmt( /* translators: %d: number of characters. */ __( '%d characters', 'mlab-seo' ), dl ) );

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
			add( 'ok', __( 'This page is hidden from search engines (noindex).', 'mlab-seo' ) );
		}

		add( width > 580 ? 'bad' : ( title.length < 30 ? 'ok' : 'good' ), width > 580 ? __( 'The title is too long and will be cut off in Google.', 'mlab-seo' ) : ( title.length < 30 ? __( 'The title is very short. Use the space for a clear description of the page.', 'mlab-seo' ) : __( 'The title fits into the Google result (up to about 580 px).', 'mlab-seo' ) ) );
		if ( ! desc ) {
			add( 'bad', __( 'No meta description: Google picks a text itself. Write one that invites a click.', 'mlab-seo' ) );
		} else if ( desc.length > 160 ) {
			add( 'ok', __( 'The meta description is longer than 160 characters and will be cut off.', 'mlab-seo' ) );
		} else if ( desc.length < 120 ) {
			add( 'ok', __( 'The meta description is short. Aim for 120 to 160 characters.', 'mlab-seo' ) );
		} else {
			add( 'good', __( 'The meta description has a good length (120 to 160 characters).', 'mlab-seo' ) );
		}

		if ( focus ) {
			var re = new RegExp( '(^|[^\\p{L}\\p{N}])' + escapeRegExp( focus ) + '(?=$|[^\\p{L}\\p{N}])', 'giu' );
			var slugFocus = focus.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, '-' );
			var intro = $.trim( doc.find( 'p' ).first().text() ).toLowerCase();
			var headingText = doc.find( 'h2,h3' ).text().toLowerCase();
			var hits = ( text.toLowerCase().match( re ) || [] ).length;
			var density = words ? ( hits * focus.split( ' ' ).length * 100 / words ) : 0;
			var d = density.toFixed( 1 );

			add( titleLower.indexOf( focus ) > -1 ? 'good' : 'bad', titleLower.indexOf( focus ) > -1 ? __( 'The focus keyword is in the SEO title.', 'mlab-seo' ) : __( 'Put the focus keyword into the SEO title, ideally near the start.', 'mlab-seo' ) );
			add( desc.toLowerCase().indexOf( focus ) > -1 ? 'good' : 'ok', desc.toLowerCase().indexOf( focus ) > -1 ? __( 'The focus keyword is in the meta description.', 'mlab-seo' ) : __( 'Use the focus keyword in the meta description.', 'mlab-seo' ) );
			add( intro.indexOf( focus ) > -1 ? 'good' : 'ok', intro.indexOf( focus ) > -1 ? __( 'The focus keyword appears in the first paragraph.', 'mlab-seo' ) : __( 'Mention the focus keyword in the first paragraph.', 'mlab-seo' ) );
			if ( ! isFront ) {
				var inUrl = decodeURIComponent( ed.slug || ed.url || '' ).toLowerCase().indexOf( slugFocus ) > -1;
				add( inUrl ? 'good' : 'ok', inUrl ? __( 'The focus keyword is in the URL.', 'mlab-seo' ) : __( 'The URL does not contain the focus keyword.', 'mlab-seo' ) );
			}
			add( headingText.indexOf( focus ) > -1 ? 'good' : 'ok', headingText.indexOf( focus ) > -1 ? __( 'The focus keyword appears in a subheading.', 'mlab-seo' ) : __( 'Use the focus keyword in at least one subheading (H2 or H3).', 'mlab-seo' ) );
			if ( words >= 100 ) {
				add( density < 0.5 ? 'ok' : ( density > 3 ? 'bad' : 'good' ), fmt( density < 0.5 ? /* translators: %s: keyword density in percent. */ __( 'Keyword density %s %% is low. Use the keyword a few more times.', 'mlab-seo' ) : ( density > 3 ? /* translators: %s: keyword density in percent. */ __( 'Keyword density %s %% is high. This can look like keyword stuffing.', 'mlab-seo' ) : /* translators: %s: keyword density in percent. */ __( 'Keyword density %s %% is fine.', 'mlab-seo' ) ), d ) );
			}
		} else {
			add( 'ok', __( 'Set a focus keyword to see the keyword checks.', 'mlab-seo' ) );
		}

		add( words >= 300 ? 'good' : ( words < 100 ? 'bad' : 'ok' ), fmt( words >= 300 ? /* translators: %d: number of words. */ __( 'The text has %d words.', 'mlab-seo' ) : ( words < 100 ? /* translators: %d: number of words. */ __( 'The text has only %d words. The page is thin: add text or hide it from search engines.', 'mlab-seo' ) : /* translators: %d: number of words. */ __( 'The text has only %d words. Pages with at least 300 words rank better.', 'mlab-seo' ) ), words ) );
		if ( doc.find( 'h1' ).length ) {
			add( 'bad', __( 'The text contains its own H1. The page title already is the H1, use H2 instead.', 'mlab-seo' ) );
		}
		add( doc.find( 'h2,h3' ).length ? 'good' : 'ok', doc.find( 'h2,h3' ).length ? __( 'The text is structured with subheadings.', 'mlab-seo' ) : __( 'Add subheadings (H2) so readers and search engines can scan the page.', 'mlab-seo' ) );

		/* Readability: long sentences and long paragraphs. */
		if ( words >= 100 ) {
			var sentences = [];
			doc.find( 'p, li' ).each( function () {
				sentences = sentences.concat( $( this ).text().split( /[.!?…]+(?:\s|$)/ ) );
			} );
			sentences = sentences.filter( function ( x ) {
				return $.trim( x ).split( /\s+/ ).length > 2;
			} );
			var long = sentences.filter( function ( x ) {
				return $.trim( x ).split( /\s+/ ).length > 20;
			} ).length;
			var share = sentences.length ? Math.round( long * 100 / sentences.length ) : 0;
			add( share > 25 ? 'ok' : 'good', share > 25 ? fmt( /* translators: %s: share of long sentences in percent. */ __( '%s %% of the sentences have more than 20 words. Shorter sentences are easier to read.', 'mlab-seo' ), share ) : __( 'Most sentences are short and easy to read.', 'mlab-seo' ) );
			var longParagraphs = doc.find( 'p' ).filter( function () {
				return $.trim( $( this ).text() ).split( /\s+/ ).length > 150;
			} ).length;
			if ( longParagraphs ) {
				add( 'ok', fmt( /* translators: %d: number of paragraphs. */ __( '%d paragraph(s) with more than 150 words. Split long paragraphs.', 'mlab-seo' ), longParagraphs ) );
			}
		}

		var missingAlt = doc.find( 'img' ).filter( function () {
			return ! $.trim( $( this ).attr( 'alt' ) || '' );
		} ).length;
		if ( doc.find( 'img' ).length ) {
			add( missingAlt ? 'ok' : 'good', missingAlt ? fmt( /* translators: %d: number of images. */ __( '%d image(s) without alternative text. Search engines and screen readers need it.', 'mlab-seo' ), missingAlt ) : __( 'All images have an alternative text.', 'mlab-seo' ) );
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
		add( internal ? 'good' : 'ok', internal ? __( 'The page links to other pages of the website.', 'mlab-seo' ) : __( 'Add at least one link to another page of the website.', 'mlab-seo' ) );
		add( external ? 'good' : 'ok', external ? __( 'The page links to external sources.', 'mlab-seo' ) : __( 'No external links. A link to a trusted source can help.', 'mlab-seo' ) );

		var hasImage = parseInt( $( '#mlab-seo-image' ).val(), 10 ) > 0 || ed.featured;
		add( hasImage ? 'good' : 'ok', hasImage ? __( 'There is an image for social media and search previews.', 'mlab-seo' ) : __( 'No image for social media. Set a social image or a featured image.', 'mlab-seo' ) );

		/* Score: good = 1, can be improved = 0.5, problem = 0. */
		var scored = list.filter( function ( c ) {
			return c.msg !== __( 'This page is hidden from search engines (noindex).', 'mlab-seo' ) && c.msg !== __( 'Set a focus keyword to see the keyword checks.', 'mlab-seo' );
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
		var label = { bad: __( 'Problem', 'mlab-seo' ), ok: __( 'Can be improved', 'mlab-seo' ), good: __( 'Good', 'mlab-seo' ) };
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
				title: __( 'Choose social image', 'mlab-seo' ),
				button: { text: __( 'Use this image', 'mlab-seo' ) },
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

/**
 * MomentumLab SEO: SEO check of a page, shared by the editor box, the editor sidebar and the page list.
 * Content is parsed with DOMParser into an inert document: no scripts run, no images load.
 *
 * @package MomentumLab\SEO
 */
( function () {
	'use strict';

	var __ = wp.i18n.__;
	var canvas = document.createElement( 'canvas' ).getContext( '2d' );
	var NOINDEX = __( 'This page is hidden from search engines (noindex).', 'mlab-seo' );
	var NO_FOCUS = __( 'Set a focus keyword to see the keyword checks.', 'mlab-seo' );

	function fmt( str, value ) {
		return String( str ).replace( /%[ds]/, value ).replace( '%%', '%' );
	}

	function escapeRegExp( s ) {
		return s.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	function textWidth( text ) {
		canvas.font = '20px Arial';
		return Math.round( canvas.measureText( text ).width );
	}

	/* Variables like %title% as on the website; %title% follows the page title. */
	function resolve( text, title, vars ) {
		vars = vars || {};
		return String( text || '' ).replace( /%%?([a-z_]+)%%?/gi, function ( match, name ) {
			name = name.toLowerCase();
			if ( 'title' === name ) {
				return title || '';
			}
			return Object.prototype.hasOwnProperty.call( vars, name ) ? vars[ name ] : match;
		} ).replace( /\s+/g, ' ' ).trim();
	}

	function parse( html ) {
		var doc = new window.DOMParser().parseFromString( '<body>' + String( html || '' ).replace( /<!--[\s\S]*?-->/g, '' ) + '</body>', 'text/html' );
		doc.querySelectorAll( 'script,style' ).forEach( function ( el ) {
			el.remove();
		} );
		return doc.body;
	}

	function text( el ) {
		return ( el ? el.textContent : '' ).replace( /\s+/g, ' ' ).trim();
	}

	function wordCount( str ) {
		return str ? str.split( /\s+/ ).length : 0;
	}

	/* Plain text of the content, for a description suggestion. */
	function plainText( html ) {
		return text( parse( html ) );
	}

	function keywordChecks( add, input, body, plain, words ) {
		var focus = input.focus;
		var title = input.title.toLowerCase();
		var desc = input.desc.toLowerCase();
		var re = new RegExp( '(^|[^\\p{L}\\p{N}])' + escapeRegExp( focus ) + '(?=$|[^\\p{L}\\p{N}])', 'giu' );
		var slugFocus = focus.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, '-' );
		var intro = text( body.querySelector( 'p' ) ).toLowerCase();
		var headings = Array.prototype.map.call( body.querySelectorAll( 'h2,h3' ), text ).join( ' ' ).toLowerCase();
		var hits = ( plain.toLowerCase().match( re ) || [] ).length;
		var density = words ? ( hits * focus.split( ' ' ).length * 100 / words ) : 0;

		add( title.indexOf( focus ) > -1 ? 'good' : 'bad', title.indexOf( focus ) > -1 ? __( 'The focus keyword is in the SEO title.', 'mlab-seo' ) : __( 'Put the focus keyword into the SEO title, ideally near the start.', 'mlab-seo' ) );
		add( desc.indexOf( focus ) > -1 ? 'good' : 'ok', desc.indexOf( focus ) > -1 ? __( 'The focus keyword is in the meta description.', 'mlab-seo' ) : __( 'Use the focus keyword in the meta description.', 'mlab-seo' ) );
		add( intro.indexOf( focus ) > -1 ? 'good' : 'ok', intro.indexOf( focus ) > -1 ? __( 'The focus keyword appears in the first paragraph.', 'mlab-seo' ) : __( 'Mention the focus keyword in the first paragraph.', 'mlab-seo' ) );
		if ( ! input.isFront ) {
			var inUrl = decodeURIComponent( input.slug || input.url || '' ).toLowerCase().indexOf( slugFocus ) > -1;
			add( inUrl ? 'good' : 'ok', inUrl ? __( 'The focus keyword is in the URL.', 'mlab-seo' ) : __( 'The URL does not contain the focus keyword.', 'mlab-seo' ) );
		}
		add( headings.indexOf( focus ) > -1 ? 'good' : 'ok', headings.indexOf( focus ) > -1 ? __( 'The focus keyword appears in a subheading.', 'mlab-seo' ) : __( 'Use the focus keyword in at least one subheading (H2 or H3).', 'mlab-seo' ) );
		if ( words >= 100 ) {
			var d = density.toFixed( 1 );
			if ( density < 0.5 ) {
				/* translators: %s: keyword density in percent. */
				add( 'ok', fmt( __( 'Keyword density %s %% is low. Use the keyword a few more times.', 'mlab-seo' ), d ) );
			} else if ( density > 3 ) {
				/* translators: %s: keyword density in percent. */
				add( 'bad', fmt( __( 'Keyword density %s %% is high. This can look like keyword stuffing.', 'mlab-seo' ), d ) );
			} else {
				/* translators: %s: keyword density in percent. */
				add( 'good', fmt( __( 'Keyword density %s %% is fine.', 'mlab-seo' ), d ) );
			}
		}
	}

	function lengthChecks( add, input ) {
		var width = textWidth( input.title );
		if ( width > 580 ) {
			add( 'bad', __( 'The title is too long and will be cut off in Google.', 'mlab-seo' ) );
		} else if ( input.title.length < 30 ) {
			add( 'ok', __( 'The title is very short. Use the space for a clear description of the page.', 'mlab-seo' ) );
		} else {
			add( 'good', __( 'The title fits into the Google result (up to about 580 px).', 'mlab-seo' ) );
		}
		if ( ! input.desc ) {
			add( 'bad', __( 'No meta description: Google picks a text itself. Write one that invites a click.', 'mlab-seo' ) );
		} else if ( input.desc.length > 160 ) {
			add( 'ok', __( 'The meta description is longer than 160 characters and will be cut off.', 'mlab-seo' ) );
		} else if ( input.desc.length < 120 ) {
			add( 'ok', __( 'The meta description is short. Aim for 120 to 160 characters.', 'mlab-seo' ) );
		} else {
			add( 'good', __( 'The meta description has a good length (120 to 160 characters).', 'mlab-seo' ) );
		}
	}

	function textChecks( add, body, words ) {
		if ( words >= 300 ) {
			/* translators: %d: number of words. */
			add( 'good', fmt( __( 'The text has %d words.', 'mlab-seo' ), words ) );
		} else if ( words < 100 ) {
			/* translators: %d: number of words. */
			add( 'bad', fmt( __( 'The text has only %d words. The page is thin: add text or hide it from search engines.', 'mlab-seo' ), words ) );
		} else {
			/* translators: %d: number of words. */
			add( 'ok', fmt( __( 'The text has only %d words. Pages with at least 300 words rank better.', 'mlab-seo' ), words ) );
		}
		if ( body.querySelector( 'h1' ) ) {
			add( 'bad', __( 'The text contains its own H1. The page title already is the H1, use H2 instead.', 'mlab-seo' ) );
		}
		var structured = body.querySelector( 'h2,h3' );
		add( structured ? 'good' : 'ok', structured ? __( 'The text is structured with subheadings.', 'mlab-seo' ) : __( 'Add subheadings (H2) so readers and search engines can scan the page.', 'mlab-seo' ) );
		if ( words < 100 ) {
			return;
		}
		var sentences = [];
		body.querySelectorAll( 'p, li' ).forEach( function ( el ) {
			sentences = sentences.concat( el.textContent.split( /[.!?…]+(?:\s|$)/ ) );
		} );
		sentences = sentences.filter( function ( x ) {
			return wordCount( x.trim() ) > 2;
		} );
		var long = sentences.filter( function ( x ) {
			return wordCount( x.trim() ) > 20;
		} ).length;
		var share = sentences.length ? Math.round( long * 100 / sentences.length ) : 0;
		/* translators: %s: share of long sentences in percent. */
		add( share > 25 ? 'ok' : 'good', share > 25 ? fmt( __( '%s %% of the sentences have more than 20 words. Shorter sentences are easier to read.', 'mlab-seo' ), share ) : __( 'Most sentences are short and easy to read.', 'mlab-seo' ) );
		var longParagraphs = Array.prototype.filter.call( body.querySelectorAll( 'p' ), function ( p ) {
			return wordCount( text( p ) ) > 150;
		} ).length;
		if ( longParagraphs ) {
			/* translators: %d: number of paragraphs. */
			add( 'ok', fmt( __( '%d paragraph(s) with more than 150 words. Split long paragraphs.', 'mlab-seo' ), longParagraphs ) );
		}
	}

	function linkChecks( add, input, body ) {
		var images = body.querySelectorAll( 'img' );
		var missingAlt = Array.prototype.filter.call( images, function ( img ) {
			return ! ( img.getAttribute( 'alt' ) || '' ).trim();
		} ).length;
		if ( images.length ) {
			/* translators: %d: number of images. */
			add( missingAlt ? 'ok' : 'good', missingAlt ? fmt( __( '%d image(s) without alternative text. Search engines and screen readers need it.', 'mlab-seo' ), missingAlt ) : __( 'All images have an alternative text.', 'mlab-seo' ) );
		}
		var home = String( input.homeUrl || '' ).replace( /^https?:/, '' );
		var internal = 0;
		var external = 0;
		body.querySelectorAll( 'a[href]' ).forEach( function ( a ) {
			var href = a.getAttribute( 'href' );
			if ( /^(mailto|tel):|^#/.test( href ) ) {
				return;
			}
			if ( ( href.charAt( 0 ) === '/' && href.charAt( 1 ) !== '/' ) || href.replace( /^https?:/, '' ).indexOf( home ) === 0 ) {
				internal++;
			} else if ( /^https?:\/\//.test( href ) ) {
				external++;
			}
		} );
		add( internal ? 'good' : 'ok', internal ? __( 'The page links to other pages of the website.', 'mlab-seo' ) : __( 'Add at least one link to another page of the website.', 'mlab-seo' ) );
		add( external ? 'good' : 'ok', external ? __( 'The page links to external sources.', 'mlab-seo' ) : __( 'No external links. A link to a trusted source can help.', 'mlab-seo' ) );
		var hasImage = input.image || input.featured;
		add( hasImage ? 'good' : 'ok', hasImage ? __( 'There is an image for social media and search previews.', 'mlab-seo' ) : __( 'No image for social media. Set a social image or a featured image.', 'mlab-seo' ) );
	}

	/**
	 * Runs all checks.
	 *
	 * @param {Object} input title, desc (own description), focus, content, slug, url, image, featured, noindex, isFront, homeUrl.
	 * @return {{list: Array<{state: string, msg: string}>, score: number}} Checks sorted problems first, score 1 to 100 (0 = nothing to score).
	 */
	function analyze( input ) {
		var list = [];
		var body = parse( input.content );
		var plain = text( body );
		var words = wordCount( plain );
		function add( state, msg ) {
			list.push( { state: state, msg: msg } );
		}
		input.focus = String( input.focus || '' ).trim().toLowerCase();
		input.title = String( input.title || '' );
		input.desc = String( input.desc || '' );

		if ( input.noindex ) {
			add( 'ok', NOINDEX );
		}
		lengthChecks( add, input );
		if ( input.focus ) {
			keywordChecks( add, input, body, plain, words );
		} else {
			add( 'ok', NO_FOCUS );
		}
		textChecks( add, body, words );
		linkChecks( add, input, body );

		/* Score: good = 1, can be improved = 0.5, problem = 0. */
		var scored = list.filter( function ( c ) {
			return c.msg !== NOINDEX && c.msg !== NO_FOCUS;
		} );
		var value = { good: 1, ok: 0.5, bad: 0 };
		var points = scored.reduce( function ( sum, c ) {
			return sum + value[ c.state ];
		}, 0 );
		var order = { bad: 0, ok: 1, good: 2 };
		list.sort( function ( a, b ) {
			return order[ a.state ] - order[ b.state ];
		} );
		return { list: list, score: scored.length ? Math.max( 1, Math.round( points * 100 / scored.length ) ) : 0 };
	}

	function state( score ) {
		if ( score >= 80 ) {
			return 'good';
		}
		return score >= 50 ? 'ok' : 'bad';
	}

	window.mlabSeoAnalysis = { analyze: analyze, resolve: resolve, textWidth: textWidth, plainText: plainText, state: state, fmt: fmt };
}() );

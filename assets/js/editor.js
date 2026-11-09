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
	var fmt = window.mlabSeoAnalysis.fmt;

	var A = window.mlabSeoAnalysis;
	function resolve( text, title ) {
		return A.resolve( text, title, cfg.vars );
	}
	var textWidth = A.textWidth;

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

	/* FAQ blocks are rendered on the server; read their items from the block editor. */
	function faqItems() {
		var blockEditor = window.wp && wp.data && wp.data.select ? wp.data.select( 'core/block-editor' ) : null;
		var items = [];
		function walk( blocks ) {
			( blocks || [] ).forEach( function ( block ) {
				if ( 'mlab-seo/faq' === block.name ) {
					items = items.concat( block.attributes.items || [] );
				}
				walk( block.innerBlocks );
			} );
		}
		if ( blockEditor && blockEditor.getBlocks ) {
			walk( blockEditor.getBlocks() );
		}
		return items.filter( function ( item ) {
			return $.trim( item.question || '' ) && $.trim( item.answer || '' );
		} );
	}

	/* FAQ text counts towards the checks. */
	function faqText() {
		return faqItems().map( function ( item ) {
			return ' <h3>' + item.question + '</h3> <p>' + item.answer + '</p> ';
		} ).join( '' );
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
		$box.find( '.mlab-seo__serp-text' ).text( desc.length > 160 ? desc.slice( 0, 157 ) + ' …' : desc );
		richSnippets( ed );

		meter( 'title', width / 580, width > 580 ? 'bad' : ( title.length < 30 ? 'ok' : 'good' ) );
		$box.find( '[data-hint="title"]' ).text( fmt( /* translators: %d: number of characters. */ __( '%d characters', 'mlab-seo' ), title.length ) + ' · ' + fmt( /* translators: %d: width in pixels. */ __( 'about %d px of 580 px', 'mlab-seo' ), width ) );
		var dl = ownDesc.length;
		meter( 'description', dl / 160, ! dl ? 'bad' : ( dl > 160 ? 'bad' : ( dl < 120 ? 'ok' : 'good' ) ) );
		$box.find( '[data-hint="description"]' ).text( fmt( /* translators: %d: number of characters. */ __( '%d characters', 'mlab-seo' ), dl ) );

		var result = checks( ed, title, ownDesc, focus );
		/* The editor sidebar shows this state; the box stays the single source of the saved values. */
		document.dispatchEvent( new window.CustomEvent( 'mlab-seo-update', { detail: {
			score: result.score,
			list: result.list,
			serpTitle: $box.find( '.mlab-seo__serp-title' ).text(),
			serpUrl: $box.find( '.mlab-seo__serp-url' ).text(),
			serpDesc: $box.find( '.mlab-seo__serp-date' ).text() + $box.find( '.mlab-seo__serp-text' ).text(),
			title: $title.val(),
			titlePlaceholder: $title.attr( 'placeholder' ) || '',
			desc: $desc.val(),
			descPlaceholder: $desc.attr( 'placeholder' ) || '',
			focus: $focus.val(),
			noindex: $( '#mlab-seo-noindex' ).is( ':checked' ),
			ogTitle: resolve( $( '#mlab-seo-og-title' ).val(), ed.title ) || title,
			ogDesc: resolve( $( '#mlab-seo-og-desc' ).val(), ed.title ) || desc,
			image: $box.find( '.mlab-seo__image-preview img' ).attr( 'src' ) || cfg.featuredImage || ''
		} } ) );
	}

	/* Changes from the editor sidebar. */
	document.addEventListener( 'mlab-seo-set', function ( e ) {
		var field = { title: $title, desc: $desc, focus: $focus }[ e.detail.field ];
		if ( field ) {
			field.val( e.detail.value ).trigger( 'input' );
		} else if ( 'noindex' === e.detail.field ) {
			$( '#mlab-seo-noindex' ).prop( 'checked', !! e.detail.value ).trigger( 'change' );
		}
		var key = { title: '_mlab_seo_title', desc: '_mlab_seo_description', focus: '_mlab_seo_focus', noindex: '_mlab_seo_noindex' }[ e.detail.field ];
		if ( key && window.wp && wp.data && wp.data.dispatch( 'core/editor' ) ) {
			/* Same value as in the box; marks the post as changed so "Update" is active. */
			var meta = {};
			meta[ key ] = 'noindex' === e.detail.field ? !! e.detail.value : String( e.detail.value );
			wp.data.dispatch( 'core/editor' ).editPost( { meta: meta } );
		}
	} );
	document.addEventListener( 'mlab-seo-ready', update );

	/* Rich snippets: breadcrumbs, date, FAQ, event dates and rating stars, as Google may show them. */
	function richSnippets( ed ) {
		var noindex = $( '#mlab-seo-noindex' ).is( ':checked' );
		var $rich = $box.find( '.mlab-seo__serp-rich' ).empty();
		var found = urlLine( ed, noindex ) ? [ __( 'Breadcrumbs', 'mlab-seo' ) ] : [];
		var date = $box.find( '.mlab-seo__serp' ).data( 'date' );

		$box.find( '.mlab-seo__serp-date' ).text( date ? date + ' — ' : '' );
		if ( noindex ) {
			$box.find( '.mlab-seo__rich-note' ).prop( 'hidden', true );
			return;
		}

		var stars = ratingLine();
		if ( stars ) {
			found.push( __( 'Rating stars', 'mlab-seo' ) );
			$rich.append( stars );
		}
		var events = eventRows();
		if ( events.length ) {
			found.push( __( 'Event', 'mlab-seo' ) );
			$rich.append( $( '<div class="mlab-seo__serp-events">' ).append( events ) );
		}
		var faq = faqItems();
		if ( faq.length ) {
			found.push( __( 'FAQ', 'mlab-seo' ) );
			$rich.append( $( '<ul class="mlab-seo__serp-faq">' ).append( faq.slice( 0, 3 ).map( function ( item ) {
				return $( '<li>' ).text( $( '<div>' ).html( item.question ).text() );
			} ) ) );
		}
		$box.find( '.mlab-seo__rich-note' )
			.text( found.length ? fmt( /* translators: %s: list of rich snippet types. */ __( 'Rich snippets from the schema.org data: %s. Google decides itself whether it shows them.', 'mlab-seo' ), found.join( ', ' ) ) + ( faq.length ? ' ' + __( 'Google shows FAQ snippets only for a few websites (for example authorities and health sites). AI assistants still use the questions.', 'mlab-seo' ) : '' ) : '' )
			.prop( 'hidden', ! found.length );
	}

	/* URL line: the breadcrumb path when the page has one. Returns true for breadcrumbs. */
	function urlLine( ed, noindex ) {
		var crumbs = noindex || isFront ? [] : ( $box.find( '.mlab-seo__serp' ).data( 'crumbs' ) || [] ).concat( ed.title ? [ ed.title ] : [] );
		var $url = $box.find( '.mlab-seo__serp-url' );
		if ( crumbs.length ) {
			$url.text( [ cfg.homeUrl.replace( /\/$/, '' ) ].concat( crumbs ).join( ' › ' ) );
		} else if ( ed.url || isFront ) {
			$url.text( isFront ? cfg.homeUrl : ed.url );
		}
		return crumbs.length > 0;
	}

	/* Event dates (Pro): up to three sub-events, or the event itself. */
	function eventRows() {
		function field( key ) {
			return $.trim( $( '#mlab-seo-event-' + key ).val() || '' );
		}
		if ( ! cfg.pro || ! field( 'name' ) || ! field( 'start' ) ) {
			return [];
		}
		var venue = field( 'location' ) || field( 'city' );
		var rows = $box.find( '.mlab-seo__subs tbody tr' ).map( function () {
			var $in = $( this ).find( 'input' );
			return { name: $.trim( $in.eq( 0 ).val() ), start: $.trim( $in.eq( 1 ).val() ), venue: $.trim( $in.eq( 2 ).val() ) || venue };
		} ).get().filter( function ( row ) {
			return row.name && row.start;
		} );
		if ( ! rows.length ) {
			rows = [ { name: field( 'name' ), start: field( 'start' ), venue: venue } ];
		}
		return rows.slice( 0, 3 ).map( function ( row ) {
			return $( '<div class="mlab-seo__serp-event">' ).append( [ eventDate( row.start ), row.name, row.venue ].map( function ( cell ) {
				return $( '<span>' ).text( cell );
			} ) );
		} );
	}

	function eventDate( value ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/.exec( value );
		if ( ! m ) {
			return value;
		}
		var opts = { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC' };
		if ( m[ 4 ] ) {
			opts.hour = '2-digit';
			opts.minute = '2-digit';
		}
		try {
			return new Intl.DateTimeFormat( cfg.locale, opts ).format( new Date( Date.UTC( +m[ 1 ], m[ 2 ] - 1, +m[ 3 ], +( m[ 4 ] || 0 ), +( m[ 5 ] || 0 ) ) ) );
		} catch ( e ) {
			return value;
		}
	}

	/* Rating stars (Pro): an aggregateRating anywhere in the additional schema. */
	function ratingLine() {
		var json;
		try {
			json = cfg.pro ? JSON.parse( $( '#mlab-seo-schema' ).val() || 'null' ) : null;
		} catch ( e ) {
			return null;
		}
		var r = findRating( json );
		var value = r ? parseFloat( String( r.ratingValue ).replace( ',', '.' ) ) : NaN;
		if ( isNaN( value ) ) {
			return null;
		}
		var best = parseFloat( r.bestRating ) || 5;
		var full = Math.max( 0, Math.min( 5, Math.round( value * 5 / best ) ) );
		var count = parseInt( r.ratingCount || r.reviewCount, 10 );
		return $( '<div class="mlab-seo__serp-rating">' )
			.append( $( '<span class="mlab-seo__serp-stars" aria-hidden="true">' ).text( '★★★★★'.slice( 0, full ) + '☆☆☆☆☆'.slice( 0, 5 - full ) ) )
			.append( document.createTextNode( ' ' + fmt( /* translators: %s: rating value, e.g. 4.5. */ __( 'Rating: %s', 'mlab-seo' ), value.toLocaleString( cfg.locale ) ) + ( count ? ' · ' + fmt( /* translators: %d: number of ratings. */ __( '%d ratings', 'mlab-seo' ), count ) : '' ) ) );
	}

	function findRating( node ) {
		if ( ! node || 'object' !== typeof node ) {
			return null;
		}
		if ( 'AggregateRating' === node[ '@type' ] ) {
			return node;
		}
		if ( node.aggregateRating && 'object' === typeof node.aggregateRating ) {
			return node.aggregateRating;
		}
		var keys = Object.keys( node );
		for ( var i = 0; i < keys.length; i++ ) {
			var hit = findRating( node[ keys[ i ] ] );
			if ( hit ) {
				return hit;
			}
		}
		return null;
	}

	function meter( name, ratio, state ) {
		$box.find( '.mlab-seo__meter[data-for="' + name + '"]' )
			.attr( 'data-state', state )
			.find( 'span' ).css( 'width', Math.min( 100, Math.round( ratio * 100 ) ) + '%' );
	}

	function checks( ed, title, desc, focus ) {
		var result = A.analyze( {
			title: title,
			desc: desc,
			focus: focus,
			content: ed.content,
			slug: ed.slug,
			url: ed.url,
			image: parseInt( $( '#mlab-seo-image' ).val(), 10 ) > 0,
			featured: ed.featured,
			noindex: $( '#mlab-seo-noindex' ).is( ':checked' ),
			isFront: isFront,
			homeUrl: cfg.homeUrl
		} );
		$( '#mlab-seo-score' ).val( result.score );
		$box.find( '.mlab-seo__score' ).text( result.score ).attr( 'data-state', A.state( result.score ) );

		var label = { bad: __( 'Problem', 'mlab-seo' ), ok: __( 'Can be improved', 'mlab-seo' ), good: __( 'Good', 'mlab-seo' ) };
		var $ul = $box.find( '.mlab-seo__checks' ).empty();
		result.list.forEach( function ( c ) {
			$( '<li>' ).attr( 'data-state', c.state )
				.append( $( '<span class="screen-reader-text">' ).text( label[ c.state ] + ': ' ) )
				.append( document.createTextNode( c.msg ) )
				.appendTo( $ul );
		} );
		return result;
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
		schedule();
	} );
}( jQuery ) );

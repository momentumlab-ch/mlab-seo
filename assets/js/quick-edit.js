/**
 * MomentumLab SEO: fills the SEO fields of Quick Edit from the SEO column.
 *
 * @package MomentumLab\SEO
 */
( function () {
	'use strict';

	if ( ! window.inlineEditPost ) {
		return;
	}
	var edit = window.inlineEditPost.edit;

	window.inlineEditPost.edit = function ( id ) {
		edit.apply( this, arguments );
		var postId = 'object' === typeof id ? this.getId( id ) : id;
		var row = document.getElementById( 'post-' + postId );
		var editRow = document.getElementById( 'edit-' + postId );
		var data = row ? row.querySelector( '.mlab-seo-quick-data' ) : null;
		if ( ! data || ! editRow ) {
			return;
		}
		editRow.querySelectorAll( '.mlab-seo-quick [data-field]' ).forEach( function ( field ) {
			var value = data.dataset[ field.dataset.field ] || '';
			if ( 'checkbox' === field.type ) {
				field.checked = '1' === value;
			} else {
				field.value = value;
			}
		} );
	};
}() );

/* global wp, kilkaCustomizerControls */
( function( api ) {
	'use strict';

	var targetUrl = ( window.kilkaCustomizerControls && window.kilkaCustomizerControls.secondBlogUrl ) ? window.kilkaCustomizerControls.secondBlogUrl : '';
	if ( ! targetUrl ) {
		return;
	}

	api.section( 'kilka_second_blog_intro_section', function( section ) {
		section.expanded.bind( function( isExpanded ) {
			if ( ! isExpanded || ! api.previewer || ! api.previewer.previewUrl ) {
				return;
			}

			if ( api.previewer.previewUrl.get() !== targetUrl ) {
				api.previewer.previewUrl.set( targetUrl );
			}
		} );
	} );
}( wp.customize ) );

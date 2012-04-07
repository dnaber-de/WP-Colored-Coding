/**
 * scripts for the admin-UI
 *
 * @package WordPress
 * @subpackage WP Colored Coding
 * @requires jQuery framework
 */
( function( $ ) {

	$( document ).ready(
		function() {

			/**
			 * buttons for the textarea
			 */
			//remember the selection
			$( '.wp-cc-codearea' ).on(
				'blur',
				function() {
					/**
					 * cross-browser selectrion
					 *
					 * @link http://stackoverflow.com/questions/263743/how-to-get-cursor-position-in-textarea
					 */
					var getInputSelection = function( el ) {
						var start = 0, end = 0, normalizedValue, range,
							textInputRange, len, endRange;
						if ( 'number' == typeof( el.selectionStart )  && 'number' == typeof( el.selectionEnd ) ) {
							start = el.selectionStart;
							end = el.selectionEnd;
						}
						else {
							range = document.selection.createRange();

							if ( range && range.parentElement() == el ) {
								len = el.value.length;
								normalizedValue = el.value.replace( /\r\n/g, "\n" );

								// Create a working TextRange that lives only in the input
								textInputRange = el.createTextRange();
								textInputRange.moveToBookmark( range.getBookmark() );

								// Check if the start and end of the selection are at the very end
								// of the input, since moveStart/moveEnd doesn't return what we want
								// in those cases
								endRange = el.createTextRange();
								endRange.collapse( false );

								if ( textInputRange.compareEndPoints( "StartToEnd", endRange ) > -1 ) {
									start = end = len;
								}
								else {
									start = -textInputRange.moveStart( 'character', -len);
									start += normalizedValue.slice( 0, start ).split( "\n" ).length - 1;

									if ( textInputRange.compareEndPoints( "EndToEnd", endRange ) > -1) {
										end = len;
									}
									else {
										end = -textInputRange.moveEnd( "character", -len );
										end += normalizedValue.slice( 0, end ).split( "\n" ).length - 1;
									}
								}
							}
						}
						return {
							start: start,
							end: end
						};
					}
					this.ccSelection = getInputSelection( this );
				}
			);

			// set the cursor to the right position
			$( '.wp-cc-codearea' ).on(
				'focus',
				function() {
					if ( 'number' == typeof( this.selectionStart ) && 'undefined' != typeof( this.ccSelection ) )
						this.selectionStart = this.selectionEnd = this.ccSelection.start;

				}
			);

			//insert a tab
			$( '.wp-cc-insert-tab' ).on(
				'click',
				function( e ) {
					e.preventDefault();
					var textarea = document.getElementById( $( this ).attr( 'data-target-id' ) );
					var selection = textarea.ccSelection;
					var len = textarea.value.length;
					textarea.value = textarea.value.substr( 0, selection.start ) + "\t" + textarea.value.substr( selection.start, len );
					textarea.ccSelection.start++;
					textarea.focus();
					return false;
				}
			);

		}
	);

} )(jQuery);

/**
 * scripts for the admin-UI
 *
 * @package WordPress
 * @subpackage WP Colored Coding
 * @requires jQuery framework
 */
 // jshint strict: true, undef: true, unused: true
( function($, wpCcGlobals) {
	"use strict";

	$( document ).ready(
		function() {

			/**
			 * buttons for the textarea
			 */
			//remember the selection
			$( document ).on(
				'blur',
				'.wp-cc-codearea',
				function() {
					/**
					 * cross-browser selectrion
					 *
					 * @link http://stackoverflow.com/questions/263743/how-to-get-cursor-position-in-textarea
					 */
					var getInputSelection = function( el ) {
						var start = 0,
							end = 0;
						if ( 'number' == typeof( el.selectionStart )  && 'number' == typeof( el.selectionEnd ) ) {
							start = el.selectionStart;
							end = el.selectionEnd;
						}
						//@todo IE support
						return {
							start: start,
							end: end
						};
					};
					this.ccSelection = getInputSelection( this );
				}
			);

			// set the cursor to the right position
			$( document ).on(
				'focus',
				'.wp-cc-codearea',
				function() {
					if ( 'number' == typeof( this.selectionStart ) && 'undefined' != typeof( this.ccSelection ) )
						this.selectionStart = this.selectionEnd = this.ccSelection.start;

				}
			);

			//insert a tab
			$( document ).on(
				'click',
				'.wp-cc-insert-tab',
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

	/**
	 * ajax stuff
	 */
	$( document ).ready(
		function() {
			// append a new codeblock section
			$( document ).on(
				'click',
				'#wp-cc-new-block',
				function() {
					$.post(
						wpCcGlobals.AjaxUrl,
						{
							nonce  : $( '#' + wpCcGlobals.NonceFieldId ).attr( 'value' ),
							action : wpCcGlobals.NewBlockAction
						},
						function( data ) {
							if ( 'undefined' == typeof data || ! data )
								return;
							data = $( data );
							data.hide();
							$( '#wp-cc-code-list' ).append( data );
							data.slideDown();
						}
					);
				}
			);

			// update (and delete) a single codeblock
			$( document ).on(
				'click',
				'.wp-cc-single-update',
				function() {
					var ns = $( this ).attr( 'data-ns' );
					var pid = wpCcGlobals.PostID;
					var fields = $( '#' + ns + ' .cc-data' );
					var data = {
						nonce  : $( '#' + wpCcGlobals.NonceFieldId ).attr( 'value' ),
						action : wpCcGlobals.UpdateBlock,
						pid    : pid
					};
					fields.each(
						function( ) {
							var name = $( this ).attr( 'name' ).match( /\[(\w+)\]$/ );
							if ( 'text' == $( this ).attr( 'type' ) || 'TEXTAREA' == this.tagName )
								data[ name[ 1 ] ] = $( this ).attr( 'value' );
							if ( 'checkbox' == $( this ).attr( 'type' ) && 'checked' == $( this ).attr( 'checked' ) )
								data[ name[ 1 ] ] = $( this ).attr( 'value' );
						}
					);
					$.post(
						wpCcGlobals.AjaxUrl,
						data,
						function( data ) {
							if ( 'undefined' == typeof data || ! data )
								return;
							if ( data.name ) {
								$( '#name-' + ns )
									.attr( 'value', data.name )
									.attr( 'readonly', 'readonly' );
							}
							if ( data.deleted ) {
								$( '#' + ns ).slideUp(
									'slow',
									function() {
										$( this ).remove();
									}
								);
							}
							else if ( data.updated ) {
								var box = $( '#' + ns + ' .cc-input' );
								box.css( { 'background-color': '#ff4' } );
								box.animate(
									{
										backgroundColor : '#f5f5f5'
									},
									500,
									function() {
										$( this ).css( { 'background-color': 'transparent' } );
									}
								);

							}
						},
						'json'
					);

				}
			);
		}
	);

	/**
	 * the dialog triggered by the TinyMCE Button
	 */
	var ccDialog  = {

		/**
		 * the formular
		 *
		 * @var Object
		 */
		popup : null,

		/**
		 * the backdrop
		 */
		backdrop : null,

		/**
		 * the editor
		 */
		editor : null,

		/**
		 * start the magic for the dialog box
		 *
		 * @return void
		 */
		init : function() {

			// any tinymce here?
			if ( 'undefined' == typeof( edCanvas ) )
				return; //no!
			//tinyMCEPopup is still undefined at this point

			ccDialog.popup = $( '#wp-cc-mce-popup' );
			ccDialog.backdrop = $( '#wp-link-backdrop' ); // don't know if there is a general backdrop for dialogs?
			ccDialog.popup.on(
				'submit',
				function( e ) {
					ccDialog.submit( e );
				}
			);
			ccDialog.popup.on(
				'wpdialogbeforeopen',
				function( e ) {
					ccDialog.updateOptions( e );
				}
			);
			// close the popup on 'ESC'
			$( document ).keyup(
				function( e ) {
					if ( 27 == e.keyCode ) {
						e.preventDefault();
						ccDialog.close();
					}
				}
			);

		},

		/**
		 * open the dialog
		 *
		 * @param editor
		 * @returns void
		 */
		open : function( editor ) {

			ccDialog.editor = editor;
			ccDialog.popup.wpdialog( {
				title : wpCcGlobals.DialogTitle,
				close : function () {
					ccDialog.backdrop.hide();
				}
			} );
			ccDialog.backdrop.show();
		},

		/**
		 * build the shortcode and append id to the cursor-possition
		 *
		 * @param e Event
		 * @return false
		 */
		submit : function( e ) {
			var sc,
				codeblock = null,
				language  = null;

			e.preventDefault();
			codeblock = ccDialog.popup.find( '#wp-cc-dialog-options-codeblocks select' ).val();
			language  = ccDialog.popup.find( '#wp-cc-dialog-options-language select' ).val();

			//no values at all?
			if ( ! codeblock && ! language ) {
				ccDialog.close();
				return false;
			}

			//the shortcode
			if ( codeblock ) {
				sc = '[cc name="' + codeblock + '"/]';
			}
			else if ( language ) {
				sc = '[cc lang="' + language + '"][/cc]';
			}

			//TinyMCE Mode (richt text editor)
			if ( ccDialog.editor ) {
				ccDialog.editor.insertContent( sc );
				ccDialog.editor.focus();
			}

			ccDialog.close();
		},

		/**
		 * get the lates shortcodes from wp
		 *
		 * @param e Event (Optional)
		 * @return void
		 */
		updateOptions : function(e) {
			var elementID = $( '#wp-cc-dialog-options-codeblocks' ).find( 'select' ).attr( 'id' );
			$.post(
				wpCcGlobals.AjaxUrl,
				{
					nonce    : $( '#wp-cc-dialog-nonce' ).val(),
					action   : wpCcGlobals.UpdateOptionsAction,
					name     : 'wp-cc-dialog-codeblocks',
					pid      : wpCcGlobals.PostID,
					el_id    : elementID
				},
				function( data ) {
					if ( 'undefined' == typeof data || ! data )
						return;
					$( '#wp-cc-dialog-options-codeblocks' ).html( data );
				}
			);
		},

		/**
		 * viewing the richtext-mode of tinymce?
		 *
		 * @return bool
		 */
		isMCE : function() {

			if (	'undefined' !== typeof( window.tinyMCEPopup ) &&
					'undefined' !== typeof( window.tinyMCEPopup.editor ) &&
					! window.tinyMCEPopup.editor.isHidden()
			) {
				return true;
			}
			return false;
		},

		/**
		 * close the popup window
		 *
		 * @param e Event (optional)
		 * @return false
		 */
		close : function( e ) {

			if ( e && 'function' == typeof( e.preventDefault ) )
				e.preventDefault();

			ccDialog.popup.wpdialog( 'close' );
			ccDialog.backdrop.hide();
		}
	};
	window.ccDialog = ccDialog;
	$( document ).ready( ccDialog.init );


	/**
	 * polyfill for datalist-elements
	 */
	$( document ).ready(
		function() {
			if ( ! window.Modernizr.input.list || ( parseInt( $.browser.version , 10) > 400 ) ) {
				$( '.cc-lang' ).relevantDropdown();
			}
		}
	);

} )(window.jQuery, window.wpCcGlobals || {});

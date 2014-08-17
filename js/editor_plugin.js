( function() {
	tinymce.create(
		'tinymce.plugins.wpCCDialog',
		{
			/**
			 * @param tinymce.Editor editor
			 * @param string url
			 */
			init : function( editor, url ) {
				/**
				 * and a new command
				 */
				editor.addCommand(
					'wp_cc_open_dialog',
					function() {
						window.ccDialog.open( editor );
					}
				);
				/**
				 * register a new button
				 */
				editor.addButton(
					'wp_cc_open',
					{
						cmd   : 'wp_cc_open_dialog',
						title : editor.getLang( 'wpCC.buttonTitle', 'CC Shortcodes' ),
						image : url + '/../img/cc.png'
					}
				);

			}
		}
	);

	// Register plugin
	tinymce.PluginManager.add( 'wpCCDialog', tinymce.plugins.wpCCDialog );
} )();

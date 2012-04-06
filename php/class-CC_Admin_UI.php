<?php

/**
 * admin interface and settings handling
 *
 * @package WordPress
 * @subpackage WP Colored Coding
 */

class CC_Admin_UI {

	/**
	 * plugin instance
	 *
	 * @access public
	 * @var WP_Colored_Coding
	 */
	public $plugin = NULL;

	/**
	 * settings_section
	 *
	 * @access protected
	 * @var string
	 */
	protected $settings_section = '';

	/**
	 * constructor
	 *
	 * @access public
	 * @param WP_Colored_Coding $plugin
	 * @return CC_Admin_UI
	 */
	public function __construct( $plugin ) {

		if ( ! $plugin instanceof WP_Colored_Coding )
			exit( 'Wrong Parameter in ' . __METHOD__  );

		$this->plugin = $plugin;
		$this->settings_section = $this->plugin->option_key . '_section';

		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_action( 'save_post', array( $this, 'update_codeblocks' ) );

	}

	/**
	 * add the metaboxes
	 *
	 * @access public
	 * @since 0.1
	 * @return void
	 */
	public function meta_boxes() {

		add_meta_box(
			'wp-cc-codeblocks',
			__( 'Code Blocks', 'wp-cc' ),
			array( $this, 'code_metabox' ),
			'post'
		);
		add_meta_box(
			'wp-cc-codeblocks',
			__( 'Code Blocks', 'wp-cc' ),
			array( $this, 'code_metabox' ),
			'page'
		);
	}

	/**
	 * the code editing metabox
	 *
	 * @access public
	 * @since 0.1
	 * @param WP_Post $post
	 * @return void
	 */
	public function code_metabox( $post ) {
		$code = $this->plugin->get_code( $post->ID );
		$code[ '' ] = array(); # append an empty section for a new codeblock
		wp_nonce_field( __CLASS__, 'wp-cc-nonce' );
		?>
		<div class="inside">
			<p>Du nutzt Syntax-Highlighting. Aktuelles Theme: Gitub</p>
			<ul>
			<?php foreach ( $code as $name => $block ) {
				$this->single_codeblock( array_merge( $block, array( 'name' => $name ) ) );
			}?>
			</ul>
			<p><input class="button-secondary" type="button" id="wp-cc-new-block" value="<?php _e( 'Give me the next block', 'wp-cc' ); ?>" /></p>
		</div>
		<?php
	}

	/**
	 * single codeblock editing markup
	 *
	 * @access public
	 * @since 0.1
	 * @param array $values (Optional)
	 * @param bool $exit (Optional)
	 * @return string|void (Void on AJAX-Requests)
	 */
	public function single_codeblock( $values = array(), $exit = FALSE ) {

		$ajax = defined( 'DOING_AJAX' ) && DOING_AJAX && $exit;
		$defaults = array(
			'name' => '',
			'code' => '',
			'lang' => ''
		);
		$ns = uniqid( '' );
		$v = wp_parse_args( $values, $defaults );

		if ( $ajax && ! wp_verify_nonce( $_POST[ 'wp-cc-nonce' ], __CLASS__ ) )
			exit;

		?>
		<li class="wp-cc-single-block">
			<div class="postbox">
				<div class="inside">
					<div>
						<p>
							<label for="name-<?php echo $ns; ?>"><?php _e( 'Name', 'wp-cc' ); ?></label><br />
							<input id="name-<?php echo $ns; ?>" type="text" name="wp-cc[<?php echo $ns; ?>][name]" value="<?php echo $v[ 'name' ]; ?>">
						</p>
						<p>
							<label for="lang-<?php echo $ns; ?>"><?php _e( 'Language', 'wp-cc' ); ?></label><br />
							<input id="lang-<?php echo $ns; ?>" type="text" name="wp-cc[<?php echo $ns; ?>][lang]" value="<?php echo $v[ 'lang' ]; ?>">
						</p>
					</div>
					<div>
						<label for="code-<?php echo $ns; ?>"><?php _e( 'Code', 'wp-cc' ); ?></label><br />
						<textarea id="code-<?php echo $ns; ?>" name="wp-cc[<?php echo $ns; ?>][code]'"><?php echo $v[ 'code' ]; ?></textarea>
					</div>
					<div>
						<input type="button" class="wp-cc-single-update button-secondary" value="<?php _e( 'Update', 'wp-cc' ); ?>" />
					</div>
				</div>
			</div>
		</li>
		<?php

		if ( $ajax )
			exit;
	}

	/**
	 * update codeblocks
	 *
	 * @access public
	 * @since 0.1
	 * @param string $post_id
	 * @return void
	 */
	public function update_codeblocks( $post_id ) {

		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		||  (  ! wp_verify_nonce( $_POST[ 'wp-cc-nonce' ], __CLASS__ ) )
		||  ( isset( $_POST[ 'post_type' ] ) && ! current_user_can( 'edit_' . $_POST[ 'post_type' ], $post_id ) )
		)
			exit( 'Busted' );

		$blocks = array();
		foreach ( $_POST[ 'wp-cc' ] as $b ) {
			if ( empty( $b[ 'name' ] ) || empty( $b[ 'code' ] ) )
				continue;
			$blocks[ $b[ 'name' ] ] = array( 'code' => $b[ 'code' ], 'lang' => $b[ 'lang' ] );
		}
		$this->plugin->set_code_blocks( $post_id, $blocks );
		$this->plugin->update_codeblocks();
	}

	/**
	 * register the settings api
	 *
	 * @access public
	 * @since 0.1
	 * @return void
	 */
	public function settings() {

		register_setting(
			'writing',
			$this->plugin->option_key,
			array( $this, 'validate_setting_input' )
		);

		add_settings_section(
			$this->settings_section,
			__( 'WP Colored Coding settings', 'wp-cc' ),
			array( $this, 'settings_description' ),
			'writing'
		);

		# use syntax highlighting?
		add_settings_field(
			'cc_use_highlighting',
			__( 'Use syntax highlighting?', 'wp-cc' ),
			array( $this, 'opt_checkbox' ),
			'writing',
			$this->settings_section,
			array(
				'id'        => 'cc_use_syntax_highlighting',
				'label_for' => 'cc_use_syntax_highlighting',
				'name'      => $this->plugin->option_key . '[use_syntax_highlighting]'
			)
		);

		# rainbow theme
		$theme_options = $this->plugin->get_themes();
		foreach ( $theme_options as $handle => $atts ) {
			$theme_options[ $handle ] = $atts[ 'name' ];
		}
		add_settings_field(
			'cc_rainbow_theme',
			__( 'Syntax highlighting theme', 'wp-cc' ),
			array( $this, 'opt_select' ),
			'writing',
			$this->settings_section,
			array(
				'id'        => 'cc_rainbow_theme',
				'label_for' => 'cc_rainbow_theme',
				'name'      => $this->plugin->option_key . '[rainbow_theme]',
				'options'   => $theme_options
			)
		);
	}

	/**
	 * prints a description to the settings section
	 *
	 * @access public
	 * @since 0.1
	 * @return void
	 */
	public function settings_description() {
		?>
		<div class="inside">
			<p><?php _e( 'If you want to use syntax highlighting via rainbow.js, just enable it and choose a theme', 'wp-cc' ); ?></p>
		</div>
		<?php
	}

	/**
	 * validate the input
	 *
	 * @access public
	 * @since 0.1
	 * @param array $input (Array of all input fields registred to the settings section)
	 * @return array
	 */
	public function validate_setting_input( $input ) {

		$return = array(
			'use_syntax_highlighting' => '',
			'rainbow_theme'           => ''
		);
		# use highlighting?
		if ( isset( $input[ 'use_syntax_highlighting' ] ) ) {
			if ( '1' === $input[ 'use_syntax_highlighting' ] )
				$return[ 'use_syntax_highlighting' ] = '1';
			else
				$return[ 'use_syntax_highlighting' ] = '0';
		}

		# rainbow theme?
		if ( isset( $input[ 'rainbow_theme' ] ) ) {
			$themes = array_keys( $this->plugin->get_themes() );
			if ( in_array( $input[ 'rainbow_theme' ], $themes ) )
				$return[ 'rainbow_theme' ] = $input[ 'rainbow_theme' ];
			else
				$return[ 'rainbow_theme' ] = '';
		}
		return $return;
	}

	/**
	 * prints a selectbox
	 *
	 * @access public
	 * @since 0.1
	 * @param array $attr
	 * @return void
	 */
	public function opt_select( $attr ) {
		$option = $this->plugin->get_options();
		$option_key = preg_replace( '~^cc_~', '', $attr[ 'id' ] );
		$current_value = $option[ $option_key ];

		?>
		<select name="<?php echo $attr[ 'name' ]; ?>" id="<?php echo $attr[ 'id' ];?>">
			<option value=""></option>
		<?php foreach ( $attr[ 'options' ] as $value => $name ) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $current_value, $value ); ?>><?php echo $name; ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * prints a checkbox
	 *
	 * @access public
	 * @since 0.1
	 * @param array $attr
	 * @return void
	 */
	public function opt_checkbox( $attr ) {
		$option = $this->plugin->get_options();
		$option_key = preg_replace( '~^cc_~', '', $attr[ 'id' ] );
		$current_value = $option[ $option_key ];
		?>
		<input type="checkbox" name="<?php echo $attr[ 'name' ]; ?>" id="<?php echo $attr[ 'id' ]; ?>" value="1" <?php checked( $current_value, '1' ); ?> />
		<?php
	}

}






















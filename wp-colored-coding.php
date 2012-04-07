<?php
/**
 * Plugin Name: WP Colored Coding
 * Plugin URI:  http://dnaber.de/
 * Author:      David Naber
 * Author URI:  http://dnaber.de/
 * Version:     0.1
 * Description: Managing Codeblocks independent from the WP Texteditor and use Rainbow.js for syntax highlighting.
 * Textdomain:  wp-cc
 */

if ( ! function_exists( 'add_filter' ) )
	exit( 'WP?' );

if ( ! class_exists( 'WP_Colored_Coding' ) ) {

	add_action( 'init', array( 'WP_Colored_Coding', 'init' ) );

	class WP_Colored_Coding {

		/**
		 * Version
		 *
		 * @cons string
		 */
		const VERSION = '0.1';

		/**
		 * filesystem path tho the plugin directory
		 *
		 * @access public
		 * @static
		 * @var string
		 */
		public static $path = '';

		/**
		 * URI to the plugin directory
		 *
		 * @access public
		 * @static
		 * @var string
		 */
		public static $uri = '';

		/**
		 * default options
		 *
		 * @access protected
		 * @static
		 * @var array
		 */
		protected static $default_options = array(
			'rainbow_theme'           => 'all-hallows-eve',
			'use_syntax_highlighting' => '1'
		);

		/**
		 * all the theme options are here
		 *
		 * @access protected
		 * @var array
		 */
		protected $options = array();

		/**
		 * options key
		 *
		 * @access public
		 * @var string
		 */
		public $option_key = '';

		/**
		 * post-meta key
		 *
		 * @access public
		 * @var string
		 */
		public $meta_key = '';

		/**
		 * codeblocks for each post
		 *
		 * @access protected
		 * @var array
		 */
		protected $code = array();

		/**
		 * themes for the rainbow.js
		 *
		 * @access protected
		 * @var array
		 */
		protected $themes = array();

		/**
		 * languages supportet by rainbow.js
		 *
		 * @access protected
		 * @var array
		 */
		protected $langs = array();

		/**
		 * scripts of rainbow.js
		 *
		 * @access protected
		 * @var array
		 */
		protected $scripts = array();

		/**
		 * instance of admin_ui class
		 *
		 * @access protected
		 * @var CC_Admin_UI
		 */
		protected $admin_ui = NULL;

		/**
		 * let's go
		 *
		 * @access public
		 * @since 0.1
		 * @static
		 * @return void
		 */
		public static function init() {

			self::$path = plugin_dir_path( __FILE__ );
			self::$uri  = plugins_url( '', __FILE__ );

			if ( class_exists( 'CC_Admin_UI' ) )
				return;

			require_once self::$path . '/php/class-CC_Admin_UI.php';
			new self( TRUE );
		}

		/**
		 * constructor
		 *
		 * @access public
		 * @since 0.1
		 * @param $hook_in (Optional, default false)
		 * @return WP_Colored_Coding
		 */
		public function __construct( $hook_in = FALSE ) {

			# set some defaults
			$this->option_key = 'wp_cc_options';
			$this->meta_key = '_wp_cc_codes';

			$this->load_options();

			# settings and admin interfaces
			$this->admin_ui = new CC_Admin_UI( $this );

			/**
			 * rainbow.js themes
			 */
			$themes = array(

				'all-hallows-eve' =>
					array(
						'src'  => 'all-hallows-eve.css',
						'name' => 'All hallows eve'
					),

				'blackboard' =>
					array(
						'src'  => 'blackboard.css',
						'name' => 'Blackboard'
					),

				'espresso-libre' =>
					array(
						'src'  => 'espresso-libre.css',
						'name' => 'Espresso libre'
					),

				'github' =>
					array(
						'src'  => 'github.css',
						'name' => 'Github'
					),

				'tricolore' =>
					array(
						'src'  => 'tricolore.css',
						'name' => 'Tricolore'
					),

				'twilight' =>
					array(
						'src'  => 'twilight.css',
						'name' => 'Twilight'
					),

				'zenburnesque' =>
					array(
						'src'  => 'zenburnesque.css',
						'name' => 'Zenburnesque'
					)
			);
			$this->themes = apply_filters( 'cc_rainbow_themes', $themes );

			$supportet_langs = array(
				'c'          => 'C',
				'php'        => 'PHP',
				'css'        => 'CSS',
				'html'       => 'HTML',
				'ruby'       => 'Ruby',
				'shell'      => 'Shell',
				'phyton'     => 'Python',
				'javascript' => 'Javascript'
			);
			$this->langs = apply_filters( 'cc_rainbow_languages', $supportet_langs );

			$scripts = array(
				'rainbow' =>
					array(
						'src' => 'rainbow.min.js',
						'depts' => array(),
					)
			);
			$this->scripts = apply_filters( 'cc_rainbow_scripts', $scripts );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
			add_shortcode( 'cc', array( $this, 'cc_block_shortcode' ) );
		}

		/**
		 * register the syntax highlighting javascripts and styles
		 *
		 * @access public
		 * @since 0.1
		 * @return void
		 */
		public function register_scripts() {

			foreach ( $this->themes as $handle => $t ) {
				wp_register_style(
					$handle,
					self::$uri . '/css/rainbow-themes/' . $t[ 'src' ],
					array(),
					self::VERSION,
					'all'
				);
			}
			if ( ! empty( $this->options[ 'rainbow_theme' ] ) && '1' === $this->options[ 'use_syntax_highlighting' ] )
				wp_enqueue_style( $this->options[ 'rainbow_theme' ] );


			foreach ( $this->scripts as $handle => $s ) {
				wp_register_script(
					$handle,
					self::$uri . '/js/' . $s[ 'src' ],
					array(),
					self::VERSION,
					TRUE # in footer
				);
			}
		}

		/**
		 * parses the shortcode
		 *
		 * @access public
		 * @since 0.1
		 * @param array $attr
		 * @return string
		 */
		public function cc_block_shortcode( $attr ) {

			$attr = shortcode_atts(
				array( 'name' => '' ),
				$attr
			);
			if ( empty( $attr[ 'name' ] ) )
				return '';

			$id = get_the_ID();
			$code = $this->get_code( $id );
			$code = $code[ $attr[ 'name' ] ];

			if ( '1' === $this->options[ 'use_syntax_highlighting' ] )
				wp_enqueue_script( 'rainbow' );
			return '<pre><code data-language="' . $code[ 'lang' ] . '">' . esc_attr( $code[ 'code' ] ) . '</code></pre>';
		}

		/**
		 * get the codeblocks for a single post or all blocks
		 *
		 * @access public
		 * @since 0.1
		 * @param int|string $post_id
		 * @return array
		 */
		public function get_code( $post_id = NULL ) {

			if ( NULL === $post_id )
				return $this->codeblocks;

			if ( ! isset( $this->codeblocks[ $post_id ] ) )
				$this->codeblocks[ $post_id ] = get_post_meta( $post_id, $this->meta_key, TRUE );

			return is_array( $this->codeblocks[ $post_id ] ) ? $this->codeblocks[ $post_id ] : array();
		}

		/**
		 * set code
		 *
		 * @access public
		 * @since 0.1
		 * @param int|string $post_id
		 * @param array $code
		 * @return void
		 */
		public function set_code_blocks( $post_id, $blocks = array() ) {

			if ( ! is_array( $blocks ) )
				$blocks = array();
			$this->codeblocks[ $post_id ] = $blocks;
			;
		}



		/**
		 * sets a single codeblock by name
		 *
		 * @access public
		 * @since 0.1
		 * @param string $post_id
		 * @param string $name
		 * @param array $value
		 * @return void
		 */
		public function set_single_block( $post_id, $name, $value = array() ) {

			if ( ! is_array( $value )  || empty( $value ) || empty( $value[ 'code' ] ) )
				unset( $this->codeblocks[ $post_id ][ $name ] );

			else
				$this->codeblocks[ $post_id ][ $name ] = $value;
		}

		/**
		 * update post metas
		 *
		 * @access public
		 * @since 0.1
		 * @return void
		 */
		public function update_codeblocks() {

			foreach ( $this->codeblocks as $id => $code ) {

				if ( empty( $code ) )
					delete_post_meta( $id, $this->meta_key );
				else
					update_post_meta( $id, $this->meta_key, $code );
			}
		}

		/**
		 * getter for the plugin options
		 *
		 * @access public
		 * @since 0.1
		 * @return void
		 */
		public function get_options() {

			return $this->options;
		}

		/**
		 * getter for registred themes
		 *
		 * @access public
		 * @since 0.1
		 * @return array
		 */
		public function get_themes() {

			return $this->themes;
		}

		/**
		 * getter for supported languages
		 *
		 * @access public
		 * @since 0.1
		 * @return array
		 */
		public function get_langs() {

			return $this->langs;
		}

		/**
		 * load options
		 *
		 * @access protected
		 * @since 0.1
		 * @return void
		 */
		protected function load_options() {

			$this->options = get_option( $this->option_key, self::$default_options );
		}


	} # end of class

}

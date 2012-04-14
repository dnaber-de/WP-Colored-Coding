<?php

/**
 * Interface between WP and Rainbow.js
 *
 * @package WP Colored Coding
 * @since 0.1
 */

add_filter( 'wp_cc_rainbow_themes',    array( 'Rainbow_API', 'themes' ) );
add_filter( 'wp_cc_rainbow_scripts',   array( 'Rainbow_API', 'scripts' ) );
add_filter( 'wp_cc_rainbow_languages', array( 'Rainbow_API', 'languages' ) );

class Rainbow_API {

	/**
	 * returns the available themes
	 *
	 * @access public
	 * @static
	 * @param array $themes
	 * @return array
	 */
	public static function themes( $themes ) {

		$default_themes = array(

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

		return array_merge( $themes, $default_themes );
	}

	/**
	 * returns the supported languages
	 *
	 * @access public
	 * @static
	 * @param array $lang
	 * @return array
	 */
	public static function languages( $lang ) {

		/**
		 * languages as 'slug' => 'name'
		 * use 'slug' for internal references
		 */
		$default_languages = array (
			'c'          => 'C',
			'php'        => 'PHP',
			'css'        => 'CSS',
			'html'       => 'HTML',
			'ruby'       => 'Ruby',
			'shell'      => 'Shell',
			'phyton'     => 'Python',
			'javascript' => 'Javascript'
		);

		return array_merge( $lang, $default_languages );
	}

	/**
	 * returns the rainbow.js script
	 *
	 * @access public
	 * @static
	 * @param array $scripts
	 * @return array
	 */
	public static function scripts( $scripts ) {

		$default_script = array(
			/**
			 * for a language-specific script use this
			 *
			'rainbow_php' =>
				array(
					'src'       => {SRC},
					'depts      => array( 'rainbow' ),
					'lang'      => 'php', # use the 'slug'
					'in_footer' => TRUE # this must be equal with the script this depends on
				),
			*/
			'rainbow' =>
				array(
					'src'       => WP_Colored_Coding::$uri . '/js/rainbow.min.js',
					'depts'     => array(),
					'in_footer' => TRUE,
					'lang'      => 'all'
				)

		);

		return array_merge( $scripts, $default_script );
	}
}

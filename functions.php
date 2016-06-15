<?php

class WSU_News_Theme {
	/**
	 * @var WSU_News_Theme
	 */
	private static $instance;

	/**
	 * Maintain and return the one instance and initiate hooks when
	 * called the first time.
	 *
	 * @return \WSU_News_Theme
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSU_News_Theme;
			self::$instance->load_plugins();
		}
		return self::$instance;
	}

	/**
	 * Load "plugins" included with the theme.
	 */
	public function load_plugins() {
		require_once( dirname( __FILE__ ) . '/includes/class-blocks-builder.php' );
	}

}

add_action( 'after_setup_theme', 'WSU_News_Theme' );
/**
 * Start things up.
 *
 * @return \WSU_News_Theme
 */
function WSU_News_Theme() {
	return WSU_News_Theme::get_instance();
}

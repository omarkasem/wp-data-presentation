<?php

/**
 * @since 1.0.0
 */
final class WP_Data_Presentation {

	/**
	 * @var string Version number of the plugin, set during initialization
	 *
	 * @since 1.0.0
	 */
	protected $_version = NULL;


	/**
	 * @var WP_Data_Presentation
	 *
	 * @since 1.0.0
	 */
	private static $_instance;


	/**
	 * WP_Data_Presentation constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version_number Current version of the plugin
	 */
	public function __construct( $version_number = '') {

		$this->_title = esc_html__( 'WP_Data_Presentation', 'wp-data-presentation' );
		$this->_version = $version_number;

		$this->_require_files();
	}

	/**
	 * Singleton instance
	 *
	 * @since 1.0.0
	 *
	 * @param string $version_number Current version of the plugin
	 *
	 * @return WP_Data_Presentation  GravityView_Plugin object
	 */
	public static function get_instance( $version_number = '') {

		if ( empty( self::$_instance ) ) {
			self::$_instance = new self( $version_number);
		}

		return self::$_instance;
	}

	/**
	 * Load all the files needed for the plugin
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function _require_files() {
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-helpers.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-post-types.php' );
		require_once( WP_DATA_PRESENTATION_ACF_PATH . 'acf.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-api.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-get-data.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-metabox.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-tables.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-shortcode.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-graphs.php' );
		require_once( WP_DATA_PRESENTATION_PATH . 'includes/class-wpdp-maps.php' );

	}


	/**
	 * Get the current version of the plugin
	 *
	 * @since 1.0.0
	 *
	 * @return string Version number
	 */
	public static function get_version() {
		return self::get_instance()->_version;
	}

}
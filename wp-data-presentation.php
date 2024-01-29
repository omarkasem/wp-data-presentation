<?php

/**
 * Plugin Name:       WP Data Presentation
 * Plugin URI:        wp-data-presentation
 * Description:       Present data using tables, graphs and maps.
 * Version:           1.0.5
 * Author:            Omar Kasem
 * Text Domain:       wp-data-presentation
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


define( 'WP_DATA_PRESENTATION_VERSION', '1.0.5' );
define( 'WP_DATA_PRESENTATION_NAME', 'wp-data-presentation' );
define( 'WP_DATA_PRESENTATION_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_DATA_PRESENTATION_PATH', plugin_dir_path( __FILE__ ) );


define( 'WP_DATA_PRESENTATION_ACF_PATH', plugin_dir_path(__FILE__) . '/lib/acf/' );
define( 'WP_DATA_PRESENTATION_ACF_URL', plugin_dir_url(__FILE__) . '/lib/acf/' );
define( 'WP_DATA_PRESENTATION_ACF_SHOW', true );



function wp_data_presentation_load() {
	require_once WP_DATA_PRESENTATION_PATH . 'class-wp-data-presentation.php';

    WP_Data_Presentation::get_instance( WP_DATA_PRESENTATION_VERSION);
}

add_action( 'plugins_loaded', 'wp_data_presentation_load', 20 );

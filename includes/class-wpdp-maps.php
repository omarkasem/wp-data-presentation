<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Maps {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Maps
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Maps
     */

    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->_add_hooks();
    }

    /**
     * Add hooks
     *
     * @since 1.0.0
     */
    private function _add_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        

    }


    function enqueue_scripts() {
        wp_register_script(WP_DATA_PRESENTATION_NAME.'google-maps-api', 'https://maps.googleapis.com/maps/api/js?key='.get_field('google_maps_api_key','option').'&callback=wpdp_maps', array(), null, true);

    }
    

    public static function shortcode_output($result){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'google-maps-api');
    ?>
    
        <div id="wpdp_map"></div>

    <?php }


}

WPDP_Maps::get_instance();
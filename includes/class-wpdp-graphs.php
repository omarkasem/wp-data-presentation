<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Graphs {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Graphs
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Graphs
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
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter', WP_DATA_PRESENTATION_URL.'assets/js/chartjs-adapter-moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment', WP_DATA_PRESENTATION_URL.'assets/js/moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs', WP_DATA_PRESENTATION_URL.'assets/js/chart.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);

    }
    

    public static function shortcode_output($result){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs2');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter');
    ?>
        <h2 id="wpdp_chart_title">
            You have to use the filter to show the chart.
        </h2>
        <select id="wpdp_type_selector" style="display:none;">
            <option value="fatalities">Fatalities</option>
            <option value="incident_count">Incident Count</option>
        </select>
        <canvas style="display:none;" id="wpdp_chart" width="800" ></canvas>
    <?php }


}

WPDP_Graphs::get_instance();